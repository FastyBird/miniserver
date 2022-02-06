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
FastyBird connector worker
"""

# Python base dependencies
import asyncio
import logging
import uuid
from typing import List, Optional

# Worker dependencies
from fastybird_devices_module.connectors.connector import Connector
from fastybird_exchange.client import IClient
from kink import di, inject

# Worker libs
from fastybird_miniserver.bootstrap import register_services
from fastybird_miniserver.workers.worker import Worker


@inject
def create_worker(
    configuration_file: str,
    connector_id: uuid.UUID,
    logger: logging.Logger = logging.getLogger("dummy"),
) -> None:
    """Create worker instance"""
    register_services(configuration_file=configuration_file)

    di[ConnectorWorker] = ConnectorWorker(
        connector_id=connector_id,
        logger=logger,
    )


@inject(
    bind={
        "connector": Connector,
    }
)
class ConnectorWorker(Worker):
    """
    Connector worker service

    @package        FastyBird:MiniServer!
    @module         workers/connector

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __connector: Connector

    __connector_id: uuid.UUID
    __connector_type: str

    # -----------------------------------------------------------------------------

    def __init__(  # pylint: disable=too-many-arguments
        self,
        connector_id: uuid.UUID,
        logger: logging.Logger,
        connector: Optional[Connector] = None,
        exchange_clients: Optional[List[IClient]] = None,
    ) -> None:
        if connector is None:
            raise Exception("Connector service is not registered")

        self.__connector = connector

        self.__connector_id = connector_id

        super().__init__(exchange_clients=exchange_clients, logger=logger)

    # -----------------------------------------------------------------------------

    async def worker_loop(self) -> None:
        """Connector coroutine"""
        try:
            self.__connector.load(connector_id=self.__connector_id)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.error(
                "The connector couldn't be loaded",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

            super().stop()

            return

        try:
            self.__connector.start()

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.error(
                "The connector couldn't be started",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

            super().stop()

            return

        try:
            while not self.is_stopped:
                self.__connector.handle()

                await asyncio.sleep(0.001)
                # time.sleep(0.001)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.error(
                "The connector worker has been unexpectedly stopped.",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

            self.stop()

    # -----------------------------------------------------------------------------

    def stop(self) -> None:
        """Worker stop handler"""
        try:
            # Terminate connector
            # if self.__connector.is_running():
            #     self.__connector.stop()
            self.__connector.stop()

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "Unexpected exception was thrown during stopping process",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

        super().stop()
