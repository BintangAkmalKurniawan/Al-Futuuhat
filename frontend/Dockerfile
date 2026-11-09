# Build dua tahap Nuxt (PRD §13.2): tahap pertama `nuxt build`,
# tahap kedua hanya menjalankan hasil build agar image lebih kecil.
FROM node:24-alpine AS build

WORKDIR /app

# Manifest dulu supaya cache dependency tidak hilang saat kode berubah
COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM node:24-alpine

WORKDIR /app
ENV NODE_ENV=production

# Hasil build Nitro: .output/server/index.mjs
COPY --from=build /app/.output ./.output

EXPOSE 3000

CMD ["node", ".output/server/index.mjs"]