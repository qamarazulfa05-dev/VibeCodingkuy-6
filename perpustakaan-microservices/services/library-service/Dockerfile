FROM node:20-alpine

WORKDIR /app

COPY package*.json ./
RUN npm install --omit=dev --no-audit --no-fund

COPY . .

ENV PORT=4002
EXPOSE 4002

CMD ["node", "server.js"]
