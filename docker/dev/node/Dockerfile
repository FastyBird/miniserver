FROM node:24-alpine

# Set working directory
WORKDIR /app

# Increase Node.js memory limit
ENV NODE_OPTIONS="--max_old_space_size=4096"

EXPOSE 3000
EXPOSE 6006

CMD ["yarn", "dev"]
