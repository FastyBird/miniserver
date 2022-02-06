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
FastyBird worker states module repositories
"""

# Python base dependencies
import uuid
from typing import Optional

# App dependencies
from fastybird_devices_module.repositories.state import (
    IChannelPropertyStateRepository,
    IDevicePropertyStateRepository,
)
from fastybird_redisdb_storage_plugin.repository import (
    StorageRepository,
    StorageRepositoryFactory,
)
from fastybird_triggers_module.repositories.state import (
    IActionStateRepository,
    IConditionStateRepository,
)
from kink import inject

# App libs
from fastybird_miniserver.state.entities import (
    ActionState,
    ChannelPropertyState,
    ConditionState,
    DevicePropertyState,
)


@inject(alias=IDevicePropertyStateRepository)
class DevicePropertiesStatesRepository(IDevicePropertyStateRepository):  # pylint: disable=too-few-public-methods
    """
    Device properties states repository

    @package        FastyBird:MiniServer!
    @module         state/repositories

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_repository: StorageRepository

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_repository_factory: StorageRepositoryFactory,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        self.__storage_repository = storage_repository_factory.create(
            host=host,
            port=port,
            database=database,
            username=username,
            password=password,
            entity=DevicePropertyState,
        )

    # -----------------------------------------------------------------------------

    def get_by_id(self, property_id: uuid.UUID) -> Optional[DevicePropertyState]:
        """Load device state from storage"""
        property_state = self.__storage_repository.find_one(item_id=property_id)

        if isinstance(property_state, DevicePropertyState) or property_state is None:
            return property_state

        raise Exception("")


@inject(alias=IChannelPropertyStateRepository)
class ChannelPropertiesStatesRepository(IChannelPropertyStateRepository):  # pylint: disable=too-few-public-methods
    """
    Channel properties states repository

    @package        FastyBird:MiniServer!
    @module         state/repositories

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_repository: StorageRepository

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_repository_factory: StorageRepositoryFactory,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        self.__storage_repository = storage_repository_factory.create(
            host=host,
            port=port,
            database=database,
            username=username,
            password=password,
            entity=ChannelPropertyState,
        )

    # -----------------------------------------------------------------------------

    def get_by_id(self, property_id: uuid.UUID) -> Optional[ChannelPropertyState]:
        """Load device state from storage"""
        property_state = self.__storage_repository.find_one(item_id=property_id)

        if isinstance(property_state, ChannelPropertyState) or property_state is None:
            return property_state

        raise Exception("")


@inject(alias=IActionStateRepository)
class ActionStatesRepository(IActionStateRepository):  # pylint: disable=too-few-public-methods
    """
    Actions states repository

    @package        FastyBird:MiniServer!
    @module         state/repositories

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_repository: StorageRepository

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_repository_factory: StorageRepositoryFactory,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        self.__storage_repository = storage_repository_factory.create(
            host=host,
            port=port,
            database=database,
            username=username,
            password=password,
            entity=ActionState,
        )

    # -----------------------------------------------------------------------------

    def get_by_id(self, action_id: uuid.UUID) -> Optional[ActionState]:
        """Load device state from storage"""
        action_state = self.__storage_repository.find_one(item_id=action_id)

        if isinstance(action_state, ActionState) or action_state is None:
            return action_state

        raise Exception("")


@inject(alias=IConditionStateRepository)
class ConditionStatesRepository(IConditionStateRepository):  # pylint: disable=too-few-public-methods
    """
    Conditions states repository

    @package        FastyBird:MiniServer!
    @module         state/repositories

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __storage_repository: StorageRepository

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        storage_repository_factory: StorageRepositoryFactory,
        host: str = "127.0.0.1",
        port: int = 6379,
        username: Optional[str] = None,
        password: Optional[str] = None,
        database: int = 0,
    ) -> None:
        self.__storage_repository = storage_repository_factory.create(
            host=host,
            port=port,
            database=database,
            username=username,
            password=password,
            entity=ConditionState,
        )

    # -----------------------------------------------------------------------------

    def get_by_id(self, condition_id: uuid.UUID) -> Optional[ConditionState]:
        """Load device state from storage"""
        condition_state = self.__storage_repository.find_one(item_id=condition_id)

        if isinstance(condition_state, ConditionState) or condition_state is None:
            return condition_state

        raise Exception("")
