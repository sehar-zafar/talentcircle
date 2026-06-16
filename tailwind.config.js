/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./Talentcircle/**/*.{html,js,ts}", "./*.html"],
  theme: {
    extend: {
      fontFamily: {
        'lato': ["Lato", "sans-serif"],
        'orbitron': ['Orbitron', 'monospace'],
        'poppins': ['Poppins', 'sans-serif']
      },
      colors: {
        primary: "#7D5FA7",
        'grayText': "#6B7385",
        'inputBg': "#EEEEEE",
        // Cyberpunk palette
        'neon-cyan': '#00ff88',
        'portal-purple': '#8b00ff',
        'cyber-pink': '#ff1493',
        'gradient-gold': '#FFD700',
        'cyber-bg': '#0a0a0a',
        'cyber-dark': '#000428',
        // Auth pages purple scale
        purple: {
          950: '#0f0a1a',
          900: '#1a0f2e',
          800: '#3a0050',
          700: '#5a0072',
          600: '#7a0094',
          500: '#9600B6',
          400: '#b300ff',
        }
      },
      animation: {
        'cyber-pulse': 'cyberPulse 2s infinite',
        'neon-glow': 'neonGlow 1.5s infinite',
        'glitch': 'glitch 0.3s infinite',
        'orbit': 'quantumOrbit 12s linear infinite',
        'portal': 'portalPulse 3s ease-in-out infinite',
        'scanline': 'scanline 8s linear infinite',
        'spark-burst': 'sparkBurst 0.6s ease-out',
        'holo-flicker': 'holoFlicker 3s infinite',
        'node-float': 'nodeFloat 6s ease-in-out infinite',
        'wireframe': 'wireframeReveal 1s ease-out',
        'float': 'float 3s ease-in-out infinite',
        'glow': 'glow 2s ease-in-out infinite alternate',
        'slide-up': 'slideUp 0.8s ease-out',
      },
      keyframes: {
        cyberPulse: {
          '0%, 100%': { transform: 'scale(1)', boxShadow: '0 0 20px rgba(0,255,136,0.6)' },
          '50%': { transform: 'scale(1.08)', boxShadow: '0 0 40px rgba(0,255,136,1)' }
        },
        neonGlow: {
          '0%, 100%': { boxShadow: '0 0 10px #00ff88, 0 0 20px #00ff88' },
          '50%': { boxShadow: '0 0 20px #00ff88, 0 0 40px #00ff88, 0 0 80px #00ff88' }
        },
        glitch: {
          '0%': { transform: 'translate(0)', textShadow: '0 0 10px #00ff88' },
          '10%': { transform: 'translate(-2px, 2px)', textShadow: '2px 0 #ff00ff, -2px 0 #00ffff' },
          '20%': { transform: 'translate(2px, -2px)', textShadow: '-2px 0 #ff00ff, 2px 0 #00ffff' },
          '100%': { transform: 'translate(0)' }
        },
        quantumOrbit: {
          '0%': { transform: 'rotate(0deg) translateX(120px) rotate(0deg)' },
          '100%': { transform: 'rotate(360deg) translateX(120px) rotate(-360deg)' }
        },
        portalPulse: {
          '0%, 100%': { boxShadow: '0 0 40px #8b5cf6, 0 0 80px #ec4899' },
          '50%': { boxShadow: '0 0 60px #8b5cf6, 0 0 100px #ec4899, 0 0 160px #06b6d4' }
        },
        scanline: {
          '0%': { transform: 'translateY(-100%)' },
          '50%': { transform: 'translateY(100vh)' }
        },
        sparkBurst: {
          '0%': { transform: 'scale(0) rotate(0deg)', opacity: 1 },
          '100%': { transform: 'scale(3) rotate(360deg)', opacity: 0 }
        },
        holoFlicker: {
          '0%, 100%': { opacity: 0.8 },
          '50%': { opacity: 0.4 }
        },
        nodeFloat: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-15px)' }
        },
wireframeReveal: {
          '0%': { clipPath: 'polygon(50% 0%, 50% 0%, 50% 100%, 50% 100%)' },
          '100%': { clipPath: 'polygon(0% 0%, 100% 0%, 100% 100%, 0% 100%)' }
        },
        float: {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        glow: {
          '0%': { boxShadow: '0 0 20px #9600B6' },
          '100%': { boxShadow: '0 0 40px #b300ff, 0 0 60px #9600B6' },
        },
        slideUp: {
          '0%': { opacity: 0, transform: 'translateY(30px)' },
          '100%': { opacity: 1, transform: 'translateY(0)' },
        }
      },
      boxShadow: {
        'neon-cyan': '0 0 25px rgba(0,255,136,0.7)',
        'neon-epic': '0 0 40px rgba(0,255,136,1), 0 0 80px rgba(139,0,255,0.7)',
        'portal-glow': '0 0 50px rgba(139,92,246,0.5)'
      }
    },
  },
  plugins: [],
}

