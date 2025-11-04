<template>
  <div class="gauge-container">
    <div class="gauge-wrapper" :style="{ width: computedSize + 'px', height: computedSize + 'px' }">
      <canvas ref="canvasRef" :width="computedSize" :height="computedSize"></canvas>

      <!-- overlay text INSIDE the circle -->
      <div class="gauge-overlay">
        <div class="gauge-value" :style="{ fontSize: (computedSize * 0.22) + 'px' }">{{ displayPercent }}%</div>
        <div class="gauge-label" v-if="label" :style="{ fontSize: (computedSize * 0.09) + 'px' }">{{ label }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount, computed } from 'vue'
import { Chart, DoughnutController, ArcElement, Tooltip } from 'chart.js'

Chart.register(DoughnutController, ArcElement, Tooltip)

const props = defineProps({
  value: { type: Number, required: true },
  max: { type: Number, required: true },
  size: { type: Number, default: 160 },
  label: { type: String, default: '' },
  animateSequence: { type: Boolean, default: true }
})

const canvasRef = ref(null)
let chart = null
const internalPercent = ref(0)

// Taille calculée pour un meilleur rendu (pixel size)
const computedSize = computed(() => Number(props.size) || 160)

const targetPercent = computed(() => {
  const m = Math.max(1, Number(props.max) || 1)
  const p = Math.round((Number(props.value) / m) * 100)
  return Math.max(0, Math.min(100, p))
})

const displayPercent = computed(() => internalPercent.value)

function pickColor(percent) {
  if (percent <= 24) return '#EF4444'
  if (percent <= 50) return '#3B82F6'
  if (percent <= 74) return '#F59E0B'
  return '#10B981'
}

function createChart() {
  if (!canvasRef.value) return
  
  const ctx = canvasRef.value.getContext('2d')
  const p = 0
  const rest = 100 - p
  
  chart = new Chart(ctx, {
    type: 'doughnut',
    data: { 
      labels: ['Value', 'Rest'],
      datasets: [{ 
        data: [p, rest], 
        backgroundColor: [pickColor(p), '#e5e7eb'],
        borderWidth: 0,
        borderRadius: 0
      }]
    },
    options: {
      responsive: false,
      maintainAspectRatio: false,
      cutout: '65%',
      rotation: -90 * (Math.PI/180), // start at top (12 o'clock)
      circumference: 360, // full circle
      plugins: {
        legend: { display: false },
        tooltip: { enabled: false }
      },
      animation: {
        duration: 800,
        easing: 'easeOutQuart'
      }
    }
  })
}

function animateTo(percent, duration = 800) {
  if (!chart) return Promise.resolve()
  
  return new Promise((resolve) => {
    const start = performance.now()
    const initial = chart.data.datasets[0].data[0] || 0
    const diff = percent - initial
    
    function step(now) {
      const t = Math.min(1, (now - start) / duration)
      const cur = Math.round(initial + diff * t)
      // guard: chart may be destroyed while animating
      if(!chart || !chart.data || !chart.data.datasets || !chart.data.datasets[0]){
        internalPercent.value = cur
        return resolve()
      }
      chart.data.datasets[0].data = [cur, 100 - cur]
      chart.data.datasets[0].backgroundColor = [pickColor(cur), '#e5e7eb']
      try{ chart.update('none') }catch(e){ /* ignore update errors */ }
      internalPercent.value = cur
      
      if (t < 1) requestAnimationFrame(step)
      else resolve()
    }
    requestAnimationFrame(step)
  })
}

async function runSequence() {
  await animateTo(100, 500)
  await animateTo(0, 300)
  await animateTo(targetPercent.value, 800)
}

onMounted(() => {
  createChart()
  if (props.animateSequence) runSequence()
  else animateTo(targetPercent.value)
})

onBeforeUnmount(() => { 
  if (chart) { 
    chart.destroy()
    chart = null 
  } 
})

watch(() => [props.value, props.max], () => {
  if (!chart) return
  animateTo(targetPercent.value, 800)
})
</script>

<style scoped>
.gauge-container {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
}

.gauge-wrapper{
  position: relative;
  display: inline-block;
}

.gauge-wrapper canvas{ display:block }

.gauge-overlay{
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  pointer-events: none; /* clicks pass through to canvas if needed */
}

.gauge-value {
  font-weight: 700;
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0;
  line-height: 1;
}

.gauge-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 4px 0 0 0;
  line-height: 1.2;
}
</style>