import { createRoot } from "react-dom/client";
import App from "./App.tsx";
import "./index.css";
import { initTelegramWebApp } from "./lib/telegram";

// Initialize Telegram WebApp API
initTelegramWebApp();

createRoot(document.getElementById("root")!).render(<App />);
