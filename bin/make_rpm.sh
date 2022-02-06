#!/bin/sh
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

# Set actual path to repo root
cd "$(dirname "$0")"
cd ..

CURRENT_VERSION=$( grep -Po 'VERSION[ ,]=[ ,]\"\K(([0-9])+(\.){0,1})+' setup.py )

CURRENT_USER=$USER

export PYTHONDONTWRITEBYTECODE=1

if [ "$1" != "only_clean" ] ; then
  echo "Create sources for RPM package..."
  find miniserver/ -name "*.pyc" -exec rm -f {} \;
  python3 setup.py bdist_rpm
  find miniserver/ -name "*.pyc" -exec rm -f {} \;

  echo "Adding the files, scripts and permissions in the package..."
  cp build/bdist.linux-x86_64/rpm/* /home/$CURRENT_USER/rpmbuild/ -r
  cp resources/build/etc/systemd/system/miniserver.service /home/$CURRENT_USER/rpmbuild/SOURCES/
  cp -r miniserver/config resources/build/etc/miniserver/
  cp -r miniserver/logs resources/build/var/log/miniserver/
  cd resources/build/etc/miniserver || echo 0 > /dev/null
  tar -zcvf configs.tar.gz config/*
  mv configs.tar.gz ../../../../
  cd ../../../../
  rm /home/$CURRENT_USER/rpmbuild/SOURCES/configs.tar.gz
  cp configs.tar.gz /home/$CURRENT_USER/rpmbuild/SOURCES/
  cp resources/build/miniserver.spec /home/$CURRENT_USER/rpmbuild/SPECS/

  echo "Building RPM package..."
  rpmbuild -ba resources/build/miniserver.spec
  cp /home/$CURRENT_USER/rpmbuild/RPMS/noarch/*.rpm .
  mv miniserver-$CURRENT_VERSION-1.noarch.rpm python3-miniserver.rpm
fi

if [ "$1" = "clean" ] || [ "$1" = "only_clean" ] ; then
  sudo rm -rf dist/
  sudo rm -rf build/
  sudo rm -rf configs.tar.gz
  sudo rm -rf miniserver-$CURRENT_VERSION.tar.gz
  sudo rm -rf miniserver-$CURRENT_VERSION.noarch.rpm
  sudo rm -rf miniserver.egg-info/
  sudo rm -rf /home/$CURRENT_USER/rpmbuild/BUILDROOT/*
  sudo find miniserver/ -name "*.pyc" -exec rm -f {} \;
fi
