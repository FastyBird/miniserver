#!/usr/bin/python3

#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

"""
FastyBird worker states module managers
"""

# Python base dependencies
import uuid
from abc import ABC
from datetime import datetime
from typing import Dict, Optional, Type, Union

# App dependencies
from fastybird_devices_module.entities.channel import ChannelPropertyEntity
from fastybird_devices_module.entities.device import DevicePropertyEntity
from fastybird_devices_module.managers.state import (
    IChannelPropertiesStatesManager,
    IDevicePropertiesStatesManager,
)
from fastybird_devices_module.state.property import (
    IChannelPropertyState,
    IDevicePropertyState,
)
from fastybird_exchange.publisher import Publisher
from fastybird_metadata.helpers import normalize_value
from fastybird_metadata.routing import RoutingKey
from fastybird_metadata.types import ButtonPayload, ModuleOrigin, SwitchPayload
from fastybird_redisdb_storage_plugin.manager import (
    StorageManager,
    StorageManagerFactory,
)
from fastybird_triggers_module.entities.action import ActionEntity
from fastybird_triggers_module.entities.condition import ConditionEntity
from fastybird_triggers_module.managers.state import (
    IActionsStatesManager,
    IConditionsStatesManager,
)
from fastybird_triggers_module.state.action import IActionState
from fastybird_triggers_module.state.condition import IConditionState
from kink import inject

# App libs
from miniserver_gateway.state.entities import (
    ActionState,
    ChannelPropertyState,
    ConditionState,
    DevicePropertyState,
    PropertyState,
)


class PropertiesStatesManager(ABC):
    """
    Base properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
        entity: Type[PropertyState],
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        self.__storage_manager = storage_manager_factory.create(
            host=host,
            port=port,
            database=database,
            username=username,
            password=password,
            entity=entity,
        )

    # -----------------------------------------------------------------------------

    def create_state(
        self,
        item_id: uuid.UUID,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> PropertyState:
        """Create new property state"""
        create_fields: Dict[str, Union[str, bool, None]] = {}

        for data_key, data_value in data.items():
            if isinstance(data_value, bool):
                create_fields[data_key] = data_value
            elif data_value is None:
                create_fields[data_key] = None
            else:
                create_fields[data_key] = str(data_value)

        if "actual_value" not in create_fields:
            create_fields["actual_value"] = None

        if "expected_value" not in create_fields:
            create_fields["expected_value"] = None
            create_fields["pending"] = False

        created_state = self.__storage_manager.create(item_id=item_id, values=create_fields)

        if not isinstance(created_state, PropertyState):
            raise Exception("")

        return created_state

    # -----------------------------------------------------------------------------

    def update_state(
        self,
        state: PropertyState,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> PropertyState:
        """Update existing property state"""
        update_fields: Dict[str, Union[str, bool, None]] = {}

        for data_key, data_value in data.items():
            if isinstance(data_value, bool):
                update_fields[data_key] = data_value
            elif data_value is None:
                update_fields[data_key] = None
            else:
                update_fields[data_key] = str(data_value)

        if "expected_value" in update_fields:
            # Expected value is different from stored actual value
            if update_fields.get("expected_value") != state.actual_value:
                update_fields["pending"] = True

            # Expected value is same stored actual value, expected value is ignored
            elif update_fields.get("expected_value") == state.actual_value:
                update_fields["expected_value"] = None
                update_fields["pending"] = False

        updated_state = self.__storage_manager.update(state=state, values=update_fields)

        if not isinstance(updated_state, PropertyState):
            raise Exception("")

        return updated_state

    # -----------------------------------------------------------------------------

    def delete_state(self, state: PropertyState) -> bool:
        """Delete existing property state"""
        return self.__storage_manager.delete(state=state)


@inject(
    alias=IDevicePropertiesStatesManager,
    bind={
        "publisher": Publisher,
    },
)
class DevicePropertiesStatesManager(PropertiesStatesManager, IDevicePropertiesStatesManager):
    """
    Device properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __publisher: Optional[Publisher] = None

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
        publisher: Optional[Publisher] = None,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        super().__init__(
            storage_manager_factory=storage_manager_factory,
            host=host,
            port=port,
            username=username,
            password=password,
            database=database,
            entity=DevicePropertyState,
        )

        self.__publisher = publisher

    # -----------------------------------------------------------------------------

    def create(
        self,
        device_property: DevicePropertyEntity,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> DevicePropertyState:
        """Create new device property state"""
        created_state = super().create_state(item_id=device_property.id, data=data)

        if not isinstance(created_state, DevicePropertyState):
            raise Exception("")

        self.__publish_property_state(device_property=device_property, state=created_state)

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        device_property: DevicePropertyEntity,
        state: IDevicePropertyState,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> DevicePropertyState:
        """Update existing device property state"""
        if not isinstance(state, PropertyState):
            raise AttributeError("Provided state entity is not valid instance")

        actual_data = state.to_dict()

        updated_state = super().update_state(state=state, data=data)

        if not isinstance(updated_state, DevicePropertyState):
            raise Exception("")

        if actual_data != updated_state.to_dict():
            self.__publish_property_state(device_property=device_property, state=updated_state)

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        device_property: DevicePropertyEntity,
        state: IDevicePropertyState,
    ) -> bool:
        """Delete existing device property state"""
        if not isinstance(state, PropertyState):
            return False

        return super().delete_state(state=state)

    # -----------------------------------------------------------------------------

    def __publish_property_state(self, device_property: DevicePropertyEntity, state: DevicePropertyState) -> None:
        if self.__publisher is None:
            return

        actual_value = normalize_value(
            data_type=device_property.data_type,
            value=state.actual_value,
            value_format=device_property.format,
        )
        expected_value = normalize_value(
            data_type=device_property.data_type,
            value=state.expected_value,
            value_format=device_property.format,
        )

        self.__publisher.publish(
            origin=ModuleOrigin.DEVICES_MODULE,
            routing_key=RoutingKey.DEVICES_PROPERTY_ENTITY_UPDATED,
            data={
                **device_property.to_dict(),
                **{
                    "actual_value": actual_value
                    if isinstance(actual_value, (str, int, float, bool)) or actual_value is None
                    else str(actual_value),
                    "expected_value": expected_value
                    if isinstance(expected_value, (str, int, float, bool)) or expected_value is None
                    else str(expected_value),
                    "pending": state.pending,
                },
            },
        )


@inject(
    alias=IChannelPropertiesStatesManager,
    bind={
        "publisher": Publisher,
    },
)
class ChannelPropertiesStatesManager(PropertiesStatesManager, IChannelPropertiesStatesManager):
    """
    Channel properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __publisher: Optional[Publisher] = None

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
        publisher: Optional[Publisher] = None,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        super().__init__(
            storage_manager_factory=storage_manager_factory,
            host=host,
            port=port,
            username=username,
            password=password,
            database=database,
            entity=ChannelPropertyState,
        )

        self.__publisher = publisher

    # -----------------------------------------------------------------------------

    def create(
        self,
        channel_property: ChannelPropertyEntity,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> ChannelPropertyState:
        """Create new channel property state"""
        created_state = super().create_state(item_id=channel_property.id, data=data)

        if not isinstance(created_state, ChannelPropertyState):
            raise Exception("")

        self.__publish_property_state(channel_property=channel_property, state=created_state)

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        channel_property: ChannelPropertyEntity,
        state: IChannelPropertyState,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> ChannelPropertyState:
        """Update existing channel property state"""
        if not isinstance(state, PropertyState):
            raise AttributeError("Provided state entity is not valid instance")

        actual_data = state.to_dict()

        updated_state = super().update_state(state=state, data=data)

        if not isinstance(updated_state, ChannelPropertyState):
            raise Exception("")

        if actual_data != updated_state.to_dict():
            self.__publish_property_state(channel_property=channel_property, state=updated_state)

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        channel_property: ChannelPropertyEntity,
        state: IChannelPropertyState,
    ) -> bool:
        """Delete existing channel property state"""
        if not isinstance(state, PropertyState):
            return False

        return super().delete_state(state=state)

    # -----------------------------------------------------------------------------

    def __publish_property_state(self, channel_property: ChannelPropertyEntity, state: ChannelPropertyState) -> None:
        if self.__publisher is None:
            return

        actual_value = normalize_value(
            data_type=channel_property.data_type,
            value=state.actual_value,
            value_format=channel_property.format,
        )
        expected_value = normalize_value(
            data_type=channel_property.data_type,
            value=state.expected_value,
            value_format=channel_property.format,
        )

        self.__publisher.publish(
            origin=ModuleOrigin.DEVICES_MODULE,
            routing_key=RoutingKey.CHANNELS_PROPERTY_ENTITY_UPDATED,
            data={
                **channel_property.to_dict(),
                **{
                    "actual_value": actual_value
                    if isinstance(actual_value, (str, int, float, bool)) or actual_value is None
                    else str(actual_value),
                    "expected_value": expected_value
                    if isinstance(expected_value, (str, int, float, bool)) or expected_value is None
                    else str(expected_value),
                    "pending": state.pending,
                },
            },
        )


class TriggersStatesManager(ABC):
    """
    Base triggers states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
        entity: Type[Union[ActionState, ConditionState]],
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        self.__storage_manager = storage_manager_factory.create(
            host=host,
            port=port,
            database=database,
            username=username,
            password=password,
            entity=entity,
        )

    # -----------------------------------------------------------------------------

    def create_state(
        self,
        item_id: uuid.UUID,
        data: Dict[str, Union[bool, None]],
    ) -> Union[ActionState, ConditionState]:
        """Create new trigger state"""
        create_fields: Dict[str, Union[str, bool, None]] = {}

        for data_key, data_value in data.items():
            if isinstance(data_value, bool):
                create_fields[data_key] = data_value
            elif data_value is None:
                create_fields[data_key] = None
            else:
                create_fields[data_key] = str(data_value)

        created_state = self.__storage_manager.create(item_id=item_id, values=create_fields)

        if not isinstance(created_state, (ActionState, ConditionState)):
            raise Exception("")

        return created_state

    # -----------------------------------------------------------------------------

    def update_state(
        self,
        state: Union[ActionState, ConditionState],
        data: Dict[str, Union[bool, None]],
    ) -> Union[ActionState, ConditionState]:
        """Update existing trigger state"""
        update_fields: Dict[str, Union[str, bool, None]] = {}

        for data_key, data_value in data.items():
            if isinstance(data_value, bool):
                update_fields[data_key] = data_value
            elif data_value is None:
                update_fields[data_key] = None
            else:
                update_fields[data_key] = str(data_value)

        updated_state = self.__storage_manager.update(state=state, values=update_fields)

        if not isinstance(updated_state, (ActionState, ConditionState)):
            raise Exception("")

        return updated_state

    # -----------------------------------------------------------------------------

    def delete_state(self, state: Union[ActionState, ConditionState]) -> bool:
        """Delete existing property state"""
        return self.__storage_manager.delete(state=state)


@inject(
    alias=IActionsStatesManager,
    bind={
        "publisher": Publisher,
    },
)
class ActionsStatesManager(TriggersStatesManager, IActionsStatesManager):
    """
    Channel properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __publisher: Optional[Publisher] = None

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
        publisher: Optional[Publisher] = None,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        super().__init__(
            storage_manager_factory=storage_manager_factory,
            host=host,
            port=port,
            username=username,
            password=password,
            database=database,
            entity=ActionState,
        )

        self.__publisher = publisher

    # -----------------------------------------------------------------------------

    def create(
        self,
        action: ActionEntity,
        data: Dict[str, Union[bool, None]],
    ) -> ActionState:
        """Create new action state"""
        created_state = super().create_state(item_id=action.id, data=data)

        if not isinstance(created_state, ActionState):
            raise Exception("")

        self.__publish_action_state(action=action, state=created_state)

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        action: ActionEntity,
        state: IActionState,
        data: Dict[str, Union[bool, None]],
    ) -> ActionState:
        """Update existing action state"""
        if not isinstance(state, ActionState):
            raise AttributeError("Provided state entity is not valid instance")

        actual_data = state.to_dict()

        updated_state = super().update_state(state=state, data=data)

        if not isinstance(updated_state, ActionState):
            raise Exception("")

        if actual_data != updated_state.to_dict():
            self.__publish_action_state(action=action, state=updated_state)

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        action: ActionEntity,
        state: IActionState,
    ) -> bool:
        """Delete existing action state"""
        if not isinstance(state, ActionState):
            return False

        return super().delete_state(state=state)

    # -----------------------------------------------------------------------------

    def __publish_action_state(self, action: ActionEntity, state: ActionState) -> None:
        if self.__publisher is None:
            return

        self.__publisher.publish(
            origin=ModuleOrigin.TRIGGERS_MODULE,
            routing_key=RoutingKey.TRIGGERS_ACTIONS_ENTITY_UPDATED,
            data={
                **action.to_dict(),
                **{
                    "is_triggered": state.is_triggered,
                },
            },
        )


@inject(
    alias=IConditionsStatesManager,
    bind={
        "publisher": Publisher,
    },
)
class ConditionsStatesManager(TriggersStatesManager, IConditionsStatesManager):
    """
    Channel properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __publisher: Optional[Publisher] = None

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
        publisher: Optional[Publisher] = None,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        super().__init__(
            storage_manager_factory=storage_manager_factory,
            host=host,
            port=port,
            username=username,
            password=password,
            database=database,
            entity=ConditionState,
        )

        self.__publisher = publisher

    # -----------------------------------------------------------------------------

    def create(
        self,
        condition: ConditionEntity,
        data: Dict[str, Union[bool, None]],
    ) -> ConditionState:
        """Create new condition state"""
        created_state = super().create_state(item_id=condition.id, data=data)

        if not isinstance(created_state, ConditionState):
            raise Exception("")

        self.__publish_condition_state(condition=condition, state=created_state)

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        condition: ConditionEntity,
        state: IConditionState,
        data: Dict[str, Union[bool, None]],
    ) -> ConditionState:
        """Update existing condition state"""
        if not isinstance(state, ConditionState):
            raise AttributeError("Provided state entity is not valid instance")

        actual_data = state.to_dict()

        updated_state = super().update_state(state=state, data=data)

        if not isinstance(updated_state, ConditionState):
            raise Exception("")

        if actual_data != updated_state.to_dict():
            self.__publish_condition_state(condition=condition, state=updated_state)

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        condition: ConditionEntity,
        state: IConditionState,
    ) -> bool:
        """Delete existing condition state"""
        if not isinstance(state, ConditionState):
            return False

        return super().delete_state(state=state)

    # -----------------------------------------------------------------------------

    def __publish_condition_state(self, condition: ConditionEntity, state: ConditionState) -> None:
        if self.__publisher is None:
            return

        self.__publisher.publish(
            origin=ModuleOrigin.TRIGGERS_MODULE,
            routing_key=RoutingKey.TRIGGERS_CONDITIONS_ENTITY_UPDATED,
            data={
                **condition.to_dict(),
                **{
                    "is_fulfilled": state.is_fulfilled,
                },
            },
        )
