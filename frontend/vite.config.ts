import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { componentTagger } from "lovable-tagger";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => {
  // Генерируем уникальный timestamp для каждой сборки (для обхода кэша Telegram mini app)
  // Вычисляется при каждом вызове defineConfig (т.е. при каждой сборке)
  const buildTimestamp = process.env.BUILD_TIME || Date.now().toString(36);
  
  return {
    base: "/frontend/",
    build: {
      outDir: "../public/frontend",
      emptyOutDir: true,
      rollupOptions: {
        output: {
          // Добавляем timestamp в имена файлов для обхода кэша Telegram mini app
          entryFileNames: `assets/index-[hash]-${buildTimestamp}.js`,
          chunkFileNames: `assets/[name]-[hash]-${buildTimestamp}.js`,
          assetFileNames: `assets/[name]-[hash]-${buildTimestamp}[extname]`,
        },
      },
    },
    server: {
      host: "::",
      port: 8080,
    },
    plugins: [react(), mode === "development" && componentTagger()].filter(Boolean),
    resolve: {
      alias: {
        "@": path.resolve(__dirname, "./src"),
      },
    },
  };
});
