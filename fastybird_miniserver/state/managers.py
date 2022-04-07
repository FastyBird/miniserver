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
from datetime import datetime
from typing import Dict, Optional, Union

# App dependencies
from fastybird_devices_module.entities.channel import ChannelPropertyEntity
from fastybird_devices_module.entities.connector import ConnectorPropertyEntity
from fastybird_devices_module.entities.device import DevicePropertyEntity
from fastybird_devices_module.managers.state import (
    IChannelPropertiesStatesManager,
    IConnectorPropertiesStatesManager,
    IDevicePropertiesStatesManager,
)
from fastybird_devices_module.state.property import (
    IChannelPropertyState,
    IConnectorPropertyState,
    IDevicePropertyState,
)
from fastybird_metadata.types import ButtonPayload, SwitchPayload
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
from fastybird_miniserver.state.entities import (
    ActionState,
    ChannelPropertyState,
    ConditionState,
    ConnectorPropertyState,
    DevicePropertyState,
    PropertyState,
)


def normalize_state_values(data: Dict) -> Dict[str, Union[str, bool, None]]:
    """Clear data to be stored in redis storage"""
    normalized_data: Dict[str, Union[str, bool, None]] = {}

    for data_key, data_value in data.items():
        if isinstance(data_value, bool):
            normalized_data[data_key] = data_value
        elif data_value is None:
            normalized_data[data_key] = None
        else:
            normalized_data[data_key] = str(data_value)

    return normalized_data


@inject(alias=IConnectorPropertiesStatesManager)
class ConnectorPropertiesStatesManager(IConnectorPropertiesStatesManager):
    """
    Connector properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
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
            entity=ConnectorPropertyState,
        )

    # -----------------------------------------------------------------------------

    def create(
        self,
        connector_property: ConnectorPropertyEntity,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> ConnectorPropertyState:
        """Create new connector property state"""
        created_state = self.__storage_manager.create(
            item_id=connector_property.id,
            values=normalize_state_values(data=data),
        )

        if not isinstance(created_state, ConnectorPropertyState):
            raise Exception("")

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        connector_property: ConnectorPropertyEntity,
        state: IConnectorPropertyState,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> ConnectorPropertyState:
        """Update existing connector property state"""
        if not isinstance(state, PropertyState):
            raise AttributeError("Provided state entity is not valid instance")

        updated_state = self.__storage_manager.update(state=state, values=normalize_state_values(data=data))

        if not isinstance(updated_state, ConnectorPropertyState):
            raise ValueError("Returned updated state is not expected state instance")

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        connector_property: ConnectorPropertyEntity,
        state: IConnectorPropertyState,
    ) -> bool:
        """Delete existing connector property state"""
        if not isinstance(state, PropertyState):
            return False

        return self.__storage_manager.delete(state=state)


@inject(alias=IDevicePropertiesStatesManager)
class DevicePropertiesStatesManager(IDevicePropertiesStatesManager):
    """
    Device properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
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
            entity=DevicePropertyState,
        )

    # -----------------------------------------------------------------------------

    def create(
        self,
        device_property: DevicePropertyEntity,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> DevicePropertyState:
        """Create new device property state"""
        created_state = self.__storage_manager.create(
            item_id=device_property.id,
            values=normalize_state_values(data=data),
        )

        if not isinstance(created_state, DevicePropertyState):
            raise Exception("")

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

        updated_state = self.__storage_manager.update(state=state, values=normalize_state_values(data=data))

        if not isinstance(updated_state, DevicePropertyState):
            raise ValueError("Returned updated state is not expected state instance")

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

        return self.__storage_manager.delete(state=state)


@inject(alias=IChannelPropertiesStatesManager)
class ChannelPropertiesStatesManager(IChannelPropertiesStatesManager):
    """
    Channel properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
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
            entity=ChannelPropertyState,
        )

    # -----------------------------------------------------------------------------

    def create(
        self,
        channel_property: ChannelPropertyEntity,
        data: Dict[str, Union[str, int, float, bool, datetime, ButtonPayload, SwitchPayload, None]],
    ) -> ChannelPropertyState:
        """Create new channel property state"""
        created_state = self.__storage_manager.create(
            item_id=channel_property.id,
            values=normalize_state_values(data=data),
        )

        if not isinstance(created_state, ChannelPropertyState):
            raise Exception("")

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

        updated_state = self.__storage_manager.update(state=state, values=normalize_state_values(data=data))

        if not isinstance(updated_state, ChannelPropertyState):
            raise ValueError("Returned updated state is not expected state instance")

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

        return self.__storage_manager.delete(state=state)


@inject(alias=IActionsStatesManager)
class ActionsStatesManager(IActionsStatesManager):
    """
    Channel properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
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
            entity=ActionState,
        )

    # -----------------------------------------------------------------------------

    def create(
        self,
        action: ActionEntity,
        data: Dict[str, Union[bool, None]],
    ) -> ActionState:
        """Create new action state"""
        created_state = self.__storage_manager.create(
            item_id=action.id,
            values=normalize_state_values(data=data),
        )

        if not isinstance(created_state, ActionEntity):
            raise Exception("")

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        action: ActionEntity,
        state: IActionState,
        data: Dict[str, Union[bool, None]],
    ) -> ActionState:
        """Update existing action state"""
        if not isinstance(state, PropertyState):
            raise AttributeError("Provided state entity is not valid instance")

        updated_state = self.__storage_manager.update(state=state, values=normalize_state_values(data=data))

        if not isinstance(updated_state, ActionState):
            raise ValueError("Returned updated state is not expected state instance")

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        action: ActionEntity,
        state: IActionState,
    ) -> bool:
        """Delete existing action state"""
        if not isinstance(state, PropertyState):
            return False

        return self.__storage_manager.delete(state=state)


@inject(alias=IConditionsStatesManager)
class ConditionsStatesManager(IConditionsStatesManager):
    """
    Channel properties states manager

    @package        FastyBird:MiniServer!
    @module         state/managers

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_manager: StorageManager

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_manager_factory: StorageManagerFactory,
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
            entity=ConditionState,
        )

    # -----------------------------------------------------------------------------

    def create(
        self,
        condition: ConditionEntity,
        data: Dict[str, Union[bool, None]],
    ) -> ConditionState:
        """Create new condition state"""
        created_state = self.__storage_manager.create(
            item_id=condition.id,
            values=normalize_state_values(data=data),
        )

        if not isinstance(created_state, ConditionState):
            raise Exception("")

        return created_state

    # -----------------------------------------------------------------------------

    def update(
        self,
        condition: ConditionEntity,
        state: IConditionState,
        data: Dict[str, Union[bool, None]],
    ) -> ConditionState:
        """Update existing condition state"""
        if not isinstance(state, PropertyState):
            raise AttributeError("Provided state entity is not valid instance")

        updated_state = self.__storage_manager.update(state=state, values=normalize_state_values(data=data))

        if not isinstance(updated_state, ConditionState):
            raise ValueError("Returned updated state is not expected state instance")

        return updated_state

    # -----------------------------------------------------------------------------

    def delete(
        self,
        condition: ConditionEntity,
        state: IConditionState,
    ) -> bool:
        """Delete existing condition state"""
        if not isinstance(state, PropertyState):
            return False

        return self.__storage_manager.delete(state=state)
