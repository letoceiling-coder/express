import { createRoot } from "react-dom/client";
import App from "./App.tsx";
import "./index.css";
import { initTelegramWebApp } from "./lib/telegram";

// Web default theme: dark.
// Migration note: once (v2) force dark for existing users to match new default,
// then preserve user's further choice via localStorage.
const THEME_PREF_VERSION = "2";
const storedTheme = localStorage.getItem("theme");
const appliedVersion = localStorage.getItem("theme_pref_version");

if (appliedVersion !== THEME_PREF_VERSION) {
  localStorage.setItem("theme", "dark");
  localStorage.setItem("theme_pref_version", THEME_PREF_VERSION);
  document.documentElement.classList.add("dark");
} else if (storedTheme === "dark") {
  document.documentElement.classList.add("dark");
} else {
  document.documentElement.classList.remove("dark");
}

// Initialize Telegram WebApp API
initTelegramWebApp();

createRoot(document.getElementById("root")!).render(<App />);
