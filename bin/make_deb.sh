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

if [ "$1" != "only_clean" ] ; then
  echo "Installing libraries for building deb package..."
  sudo apt-get install python3-stdeb fakeroot python-all dh-python -y

  echo "Building web ui..."
  git submodule init
  git submodule update
  cd web-ui
  sudo yarn cache clean
  sudo yarn install --network-timeout 1000000
  sudo yarn generate
  cd ..
  sudo mkdir public || echo
  sudo cp -r web-ui/dist/. public/

  echo "Adding the files & folders, scripts in the package..."
  sudo rm -rf dist/
  sudo mkdir dist || echo
  sudo cp -r resources/build/etc dist
  sudo cp -r resources/build/usr dist
  sudo cp -r resources/build/var dist
  sudo cp -r -a resources/build/DEBIAN dist
  sudo cp -r deb_dist/miniserver-$CURRENT_VERSION/debian/python3-miniserver/DEBIAN dist
  sudo cp -r deb_dist/miniserver-$CURRENT_VERSION/debian/python3-miniserver/usr dist
  sudo mv dist/usr/bin/miniserver dist/usr/bin/fb-miniserver
  sudo cp -r miniserver/config resources/build/etc/miniserver
  sudo sed -i 's/\.\/logs/\/var\/log\/miniserver/g' resources/build/etc/miniserver/config/logs.conf >> resources/build/etc/miniserver/config/logs.conf
  sudo cp -r miniserver/logs/. resources/build/var/log/miniserver/
  sudo find dist/ -name "*.gitignore" -exec rm -f {} \;

  echo "Creating sources for DEB package..."
  sudo find miniserver/ -name "*.pyc" -exec rm -f {} \;
  python3 setup.py --command-packages=stdeb.command bdist_deb
  sudo find miniserver/ -name "*.pyc" -exec rm -f {} \;
  sudo mkdir -p dist/usr/lib/miniserver || echo
  sudo cp -r src dist/usr/lib/miniserver/
  sudo cp -r public dist/usr/lib/miniserver/
  sudo cp -r vendor dist/usr/lib/miniserver/
  sudo cp -r config dist/etc/miniserver/
  sudo chmod +x dist/etc/ -R
  sudo chmod +x dist/usr/ -R
  sudo chmod +x dist/var/ -R
  sudo rm dist/etc/miniserver/config/local.neon || echo

  echo "Adding permissions in the package..."
  sudo chown root:root dist/ -R
  sudo chmod 0775 dist/DEBIAN/config
  sudo chmod 0775 dist/DEBIAN/postinst
  sudo chmod 0775 dist/DEBIAN/prerm

  echo "Building DEB package..."
  dpkg-deb -b dist fb-miniserver.deb

  echo "Application package was created"
fi

if [ "$1" = "clean" ] || [ "$1" = "only_clean" ] ; then
  sudo rm -rf deb_dist/
  sudo rm -rf dist/
  sudo rm -rf miniserver.egg-info/
  sudo rm -rf miniserver-$CURRENT_VERSION.tar.gz
  sudo find miniserver/ -name "*.pyc" -exec rm -f {} \;
fi

if [ "$1" = "only_clean" ] ; then
  sudo rm fb-miniserver.deb
fi
