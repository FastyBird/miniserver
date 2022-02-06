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
import signal
import time
from abc import ABC, abstractmethod
from threading import Thread
from types import FrameType
from typing import List, Optional

# Worker dependencies
from fastybird_exchange.client import IClient
from kink import di


class Worker(ABC):
    """
    Worker service

    @package        FastyBird:MiniServer!
    @module         workers/worker

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __stopped: bool = False

    __exchange_clients: List[IClient] = []

    _logger: logging.Logger

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        exchange_clients: Optional[List[IClient]] = None,
        logger: logging.Logger = logging.getLogger("dummy"),
    ) -> None:
        # Exchange clients are injected from DI
        if exchange_clients is None:
            self.__exchange_clients = []
        else:
            self.__exchange_clients = exchange_clients

        # self.__connector_thread = Thread(name="Connector thread", daemon=True, target=self.connector_loop)
        self.__exchange_thread = Thread(name="Exchange thread", daemon=True, target=self.exchange_loop)

        # Configure signal handlers
        signal.signal(signal.SIGINT, self.sigterm_handler)
        signal.signal(signal.SIGTERM, self.sigterm_handler)

        self._logger = logger

        self.__stopped = False

        # Register event loop into DI container
        di["loop"] = asyncio.get_event_loop()

        # Register worker coroutines
        # asyncio.ensure_future(self.exchange_loop())
        asyncio.ensure_future(self.worker_loop())

        # self.__connector_thread.start()
        self.__exchange_thread.start()

        # while not self.__stopped:
        #     # Just to keep worker running
        #     time.sleep(1)

        # Run loop with coroutines
        di["loop"].run_forever()

    # -----------------------------------------------------------------------------

    @property
    def is_stopped(self) -> bool:
        """Check if worker is stopped or not"""
        return self.__stopped

    # -----------------------------------------------------------------------------

    @abstractmethod
    async def worker_loop(self) -> None:
        """Connector coroutine"""

    # -----------------------------------------------------------------------------

    def exchange_loop(self) -> None:
        """Data exchange coroutine"""
        try:
            for client in self.__exchange_clients:
                client.start()

            while not self.__stopped:
                for client in self.__exchange_clients:
                    client.handle()

                # await asyncio.sleep(0.001)
                time.sleep(0.001)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.error(
                "The exchange worker has been unexpectedly stopped.",
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
        # Mark worker as stopped
        self.__stopped = True

        try:
            # Terminate all exchange services
            for exchange_client in self.__exchange_clients:
                exchange_client.stop()

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.error(
                "Unexpected exception was thrown during stopping process",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

        if di["loop"].is_running():
            di["loop"].stop()

        self._logger.info("===========================")
        self._logger.info("The worker has been stopped")

    # -----------------------------------------------------------------------------

    def sigterm_handler(
        self,
        signals: int,  # pylint: disable=unused-argument
        frame: Optional[FrameType],  # pylint: disable=unused-argument
    ) -> None:
        """Handle termination request"""
        self.stop()
