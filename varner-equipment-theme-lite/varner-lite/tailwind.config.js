/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./**/*.php",
    "./partials/**/*.php",
    "./src/**/*.css"
  ],
  safelist: [
    // Text sizes
    "text-[8px]", "text-[9px]", "text-[11px]", "text-[12px]", "text-[15px]", "text-[46px]",
    // Tracking (letter-spacing)
    "tracking-[0.1em]", "tracking-[0.2em]", "tracking-[0.25em]", "tracking-[0.3em]", "tracking-[0.4em]",
    // Border radius
    "rounded-[1.5rem]", "rounded-[2rem]", "rounded-[2.5rem]", "rounded-[3rem]",
    // Line height
    "leading-[0.9]", "leading-[1]", "leading-[1.1]",
    // Aspect ratio
    "aspect-[16/10]", "aspect-[16/11]", "aspect-[4/3]",
    // Widths
    "w-[85%]", "w-[280px]", "w-[320px]", "w-[520px]",
    // Max widths
    "max-w-[200px]", "max-w-[240px]", "max-w-[250px]", "max-w-[280px]",
    // Heights
    "h-[5px]", "h-[85%]", "h-[400px]", "h-[500px]",
    // Max heights
    "max-h-[320px]", "max-h-[75vh]", "max-h-[80vh]",
    // Min heights
    "min-h-[80px]", "min-h-[85px]", "min-h-[120px]", "min-h-[160px]",
    "min-h-[400px]", "min-h-[750px]", "min-h-[60vh]", "min-h-[80vh]", "min-h-[85vh]",
    "min-h-[1rem]",
    // Min widths
    "min-w-[140px]", "min-w-[200px]",
    // Padding
    "py-[20px]",
    // Z-index
    "z-[100]", "z-[1000]", "z-[9999]",
    // Effects
    "blur-[100px]", "drop-shadow-[0_20px_50px_rgba(0,0,0,0.3)]",
    "translate-y-[-2px]",
    // Shadows (complex rgba values)
    "shadow-[0_0_30px_rgba(220,38,38,0.5)]",
    "shadow-[0_10px_40px_rgba(0,0,0,0.1)]",
    "shadow-[0_20px_50px_rgba(0,0,0,0.2)]",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
