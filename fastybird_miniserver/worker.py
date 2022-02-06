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
FastyBird worker
"""

# Python base dependencies
import uuid
from os import path

# Worker dependencies
import click

# Worker libs
from miniserver_gateway.workers.automator import (
    create_worker as create_automator_worker,
)
from miniserver_gateway.workers.connector import (
    create_worker as create_connector_worker,
)


@click.group()
def cli() -> None:
    """Console commands group"""


@click.command(name="fb:connector:start")
def connector() -> None:
    """Run connector worker as stand-alone instance"""
    create_connector_worker(
        configuration_file=path.dirname(path.abspath(__file__)) + "/../config/fb_gateway.yaml".replace("/", path.sep),
        #  connector_id=uuid.UUID("17c59dfa-2edd-438e-8c49-faa4e38e5a5e")  # fb-bus
        #  connector_id=uuid.UUID("20fba951-e76d-4d6b-a572-dec02c6d8de8")  # shelly
        connector_id=uuid.UUID("bbcccf8c-33ab-431b-a795-d7bb38b6b6db")  # modbus
    )


@click.command(name="fb:automator:start")
def automator() -> None:
    """Run automator worker as stand-alone instance"""
    create_automator_worker(
        configuration_file=path.dirname(path.abspath(__file__)) + "/../config/fb_gateway.yaml".replace("/", path.sep),
    )


cli.add_command(connector)
cli.add_command(automator)


if __name__ == "__main__":
    cli()
