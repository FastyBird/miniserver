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
  sudo apt-get install fakeroot -y

  echo "Adding the files & folders, scripts in the package..."
  sudo mkdir dist || echo
  sudo cp -r resources/build/etc dist
  sudo cp -r resources/build/usr dist
  sudo cp -r resources/build/var dist
  sudo cp -r -a resources/build/DEBIAN dist
  sudo find dist/ -name "*.gitignore" -exec rm -f {} \;

  echo "Creating sources for DEB package..."
  sudo mkdir -p dist/usr/lib/miniserver-manager || echo
  sudo cp -r src dist/usr/lib/miniserver-manager/
  sudo cp -r public dist/usr/lib/miniserver-manager/
  sudo cp -r vendor dist/usr/lib/miniserver-manager/
  sudo cp -r config dist/etc/miniserver-manager/
  sudo chmod +x dist/etc/ -R
  sudo chmod +x dist/usr/ -R
  sudo chmod +x dist/var/ -R
  sudo rm dist/etc/miniserver-manager/config/local.neon || echo

  echo "Adding permissions in the package..."
  sudo chown root:root dist/ -R
  sudo chmod +x dist/DEBIAN/preinst
  sudo chmod +x dist/DEBIAN/postinst
  sudo chmod +x dist/DEBIAN/prerm
  sudo chmod +x dist/DEBIAN/postrm
  sudo chown root:root dist/DEBIAN/preinst
  sudo chown root:root dist/DEBIAN/postinst
  sudo chown root:root dist/DEBIAN/prerm
  sudo chown root:root dist/DEBIAN/postrm

  echo "Building DEB package..."
  dpkg-deb -b dist fb-miniserver-manager.deb

  echo "Application package was created"
fi

if [ "$1" = "clean" ] || [ "$1" = "only_clean" ] ; then
  sudo rm -rf -R dist
fi

if [ "$1" = "only_clean" ] ; then
  sudo rm fb-miniserver-manager.deb
fi
