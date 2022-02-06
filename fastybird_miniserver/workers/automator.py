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
FastyBird automator worker
"""

# Python base dependencies
import asyncio
import logging
from typing import List, Optional

# Worker dependencies
from fastybird_exchange.client import IClient
from fastybird_triggers_module.automation.automation import Automator
from kink import di, inject

# Worker libs
from miniserver_gateway.bootstrap import register_services
from miniserver_gateway.workers.worker import Worker


@inject
def create_worker(configuration_file: str, logger: logging.Logger = logging.getLogger("dummy")) -> None:
    """Create worker instance"""
    register_services(configuration_file=configuration_file)

    di[AutomatorWorker] = AutomatorWorker(logger=logger)


@inject(
    bind={
        "automator": Automator,
    }
)
class AutomatorWorker(Worker):
    """
    Automator worker service

    @package        FastyBird:MiniServer!
    @module         workers/automator

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __automator: Automator

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        logger: logging.Logger,
        automator: Optional[Automator] = None,
        exchange_clients: Optional[List[IClient]] = None,
    ) -> None:
        if automator is None:
            raise Exception("Automator service is not registered")

        self.__automator = automator

        super().__init__(exchange_clients=exchange_clients, logger=logger)

    # -----------------------------------------------------------------------------

    async def worker_loop(self) -> None:
        """Connector coroutine"""
        try:
            self.__automator.start()

            while not self.is_stopped:
                self.__automator.handle()

                await asyncio.sleep(0.001)
                # time.sleep(0.001)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "The automator worker has been unexpectedly stopped.",
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
            # Terminate automator
            self.__automator.stop()

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
