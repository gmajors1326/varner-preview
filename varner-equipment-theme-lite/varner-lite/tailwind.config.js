/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./**/*.php",
    "./partials/**/*.php",
    "./src/**/*.css"
  ],
  safelist: [
    // Arbitrary values used across PHP templates (not auto-detected by JIT)
    "text-[8px]", "text-[9px]", "text-[10px]", "text-[11px]", "text-[12px]", "text-[15px]", "text-[46px]",
    "tracking-[0.1em]", "tracking-[0.2em]", "tracking-[0.25em]", "tracking-[0.3em]", "tracking-[0.4em]",
    "rounded-[1.5rem]", "rounded-[2rem]", "rounded-[2.5rem]", "rounded-[3rem]",
    "leading-[0.9]", "leading-[1]", "leading-[1.1]",
    "aspect-[16/10]", "aspect-[16/11]", "aspect-[4/3]",
    "w-[280px]", "w-[85%]", "w-[90vw]",
    "h-[400px]", "h-[5px]", "h-[85%]",
    "py-[20px]",
    "z-[100]", "z-[1000]", "z-[9999]",
    "blur-[100px]",
    "shadow-[0_0_30px_rgba(220,38,38,0.5)]",
    "shadow-[0_10px_40px_rgba(0,0,0,0.1)]",
    "shadow-[0_20px_50px_rgba(0,0,0,0.2)]",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
