FROM node:24-alpine

# Set working directory
WORKDIR /app

# Increase Node.js memory limit
ENV NODE_OPTIONS="--max_old_space_size=4096"

# npm-installed rather than via corepack -- keeps prod, dev and CI on one
# install mechanism. Dependencies themselves come from the bind-mounted volume,
# same as before; this image installs no packages of its own.
RUN npm i -g pnpm@10.34.5

EXPOSE 3000
EXPOSE 6006

CMD ["pnpm", "dev"]
