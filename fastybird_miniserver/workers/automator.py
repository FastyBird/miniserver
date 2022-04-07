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
from typing import Optional

# Worker dependencies
from fastybird_exchange.client import IClient
from fastybird_triggers_module.automation.automation import Automator
from kink import di, inject

# Worker libs
from fastybird_miniserver.bootstrap import register_services
from fastybird_miniserver.exchange.queue import ConsumerQueue, PublisherQueue
from fastybird_miniserver.workers.worker import Worker


@inject
def create_worker(configuration_file: str, logger: logging.Logger = logging.getLogger("dummy")) -> None:
    """Create worker instance"""
    register_services(configuration_file=configuration_file)

    di[AutomatorWorker] = AutomatorWorker(logger=logger)


@inject(
    bind={
        "automator": Automator,
        "exchange_client": IClient,
        "consumer_queue": ConsumerQueue,
        "publisher_queue": PublisherQueue,
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
        consumer_queue: ConsumerQueue,
        publisher_queue: PublisherQueue,
        automator: Optional[Automator] = None,
        exchange_client: Optional[IClient] = None,
    ) -> None:
        if automator is None:
            raise Exception("Automator service is not registered")

        self.__automator = automator

        super().__init__(
            exchange_client=exchange_client,
            consumer_queue=consumer_queue,
            publisher_queue=publisher_queue,
            logger=logger,
        )

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
