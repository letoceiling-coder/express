import { createRoot } from "react-dom/client";
import App from "./App.tsx";
import "./index.css";
import { initTelegramWebApp } from "./lib/telegram";

// Web default theme: dark (if user hasn't selected one yet)
const storedTheme = localStorage.getItem("theme");
if (!storedTheme) {
  localStorage.setItem("theme", "dark");
  document.documentElement.classList.add("dark");
} else if (storedTheme === "dark") {
  document.documentElement.classList.add("dark");
} else {
  document.documentElement.classList.remove("dark");
}

// Initialize Telegram WebApp API
initTelegramWebApp();

createRoot(document.getElementById("root")!).render(<App />);
