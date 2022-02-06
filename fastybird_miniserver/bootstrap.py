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
FastyBird worker DI container
"""

# pylint: disable=no-value-for-parameter

# Python base dependencies
import logging
import logging.config
import logging.handlers

# Worker dependencies
from fastybird_devices_module.bootstrap import (
    register_services as register_services_devices_module,
)
from fastybird_devices_module.entities.base import Base as DevicesModuleBase
from fastybird_exchange.bootstrap import register_services as register_services_exchange
from fastybird_redisdb_exchange_plugin.bootstrap import (
    register_services as register_services_redisdb_exchange_plugin,
)
from fastybird_redisdb_storage_plugin.bootstrap import (
    register_services as register_services_redisdb_storage_plugin,
)
from fastybird_triggers_module.bootstrap import (
    register_services as register_services_triggers_module,
)
from fastybird_triggers_module.entities.base import Base as TriggersModuleBase
from kink import di
from sqlalchemy import create_engine  # type: ignore[import]
from sqlalchemy.orm import Session  # type: ignore[import]
from whistle import EventDispatcher
from yaml import safe_load

# Worker libs
from miniserver_gateway.logger import get_logger
from miniserver_gateway.state.managers import (
    ActionsStatesManager,
    ChannelPropertiesStatesManager,
    ConditionsStatesManager,
    DevicePropertiesStatesManager,
)
from miniserver_gateway.state.repositories import (
    ActionStatesRepository,
    ChannelPropertiesStatesRepository,
    ConditionStatesRepository,
    DevicePropertiesStatesRepository,
)


def register_services(configuration_file: str) -> None:
    """Register worker services"""
    with open(file=configuration_file, encoding="utf-8") as worker_configuration:
        configuration = safe_load(worker_configuration)

    # Base logger channel
    di[logging.Logger] = get_logger(channel="service")
    di["worker_logger-service"] = di[logging.Logger]

    # Worker event dispatcher service
    di[EventDispatcher] = EventDispatcher()

    # PREPARE CONFIGURATION

    # Extract configuration for Database services from configuration file
    database_configuration = configuration.get("database", {})
    assert isinstance(database_configuration, dict)
    di["db_host"] = database_configuration.get("host")
    di["db_username"] = database_configuration.get("user")
    di["db_password"] = database_configuration.get("passwd")
    di["db_database"] = database_configuration.get("db")

    di["db_engine"] = create_engine(
        f"mysql+pymysql://{di['db_username']}:{di['db_password']}@{di['db_host']}/{di['db_database']}",
        echo=False,
    )
    di[Session] = Session(di["db_engine"])
    di["db_session"] = di[Session]

    # Initialize all database models
    DevicesModuleBase.metadata.create_all(di["db_engine"])
    TriggersModuleBase.metadata.create_all(di["db_engine"])

    # Extract configuration for Redis services from configuration file
    redis_configuration = configuration.get("redis", {})
    assert isinstance(redis_configuration, dict)
    di["redis_host"] = str(redis_configuration.get("host", "127.0.0.1"))
    di["redis_port"] = int(str(redis_configuration.get("port", 6379)))
    if redis_configuration.get("username", None) is not None:
        di["redis_username"] = str(redis_configuration.get("username", None))
    if redis_configuration.get("password", None) is not None:
        di["redis_password"] = str(redis_configuration.get("password", None))

    # INITIALIZE PLUGINS

    register_services_exchange()
    register_services_redisdb_storage_plugin(logger=get_logger(channel="storage"))
    register_services_redisdb_exchange_plugin(
        settings={
            "host": di["redis_host"],
            "port": di["redis_port"],
            "username": di["redis_username"] if "redis_username" in di else None,
            "password": di["redis_password"] if "redis_password" in di else None,
        },
        logger=get_logger(channel="exchange"),
    )

    # INITIALIZE STATES SERVICES

    # DEVICES MODULE
    di[DevicePropertiesStatesManager] = DevicePropertiesStatesManager(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 0))),
    )
    di[DevicePropertiesStatesRepository] = DevicePropertiesStatesRepository(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 0))),
    )
    di[ChannelPropertiesStatesManager] = ChannelPropertiesStatesManager(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 0))),
    )
    di[ChannelPropertiesStatesRepository] = ChannelPropertiesStatesRepository(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 0))),
    )

    # TRIGGERS MODULE
    di[ActionsStatesManager] = ActionsStatesManager(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 1))),
    )
    di[ActionStatesRepository] = ActionStatesRepository(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 1))),
    )
    di[ConditionsStatesManager] = ConditionsStatesManager(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 1))),
    )
    di[ConditionStatesRepository] = ConditionStatesRepository(  # type: ignore[call-arg]
        host=di["redis_host"],
        port=di["redis_port"],
        username=di["redis_username"] if "redis_username" in di else None,
        password=di["redis_password"] if "redis_password" in di else None,
        database=int(str(redis_configuration.get("database", 1))),
    )

    # INITIALIZE MODULES
    modules_logger = get_logger(channel="module")

    register_services_devices_module(logger=modules_logger)
    register_services_triggers_module(logger=modules_logger)
