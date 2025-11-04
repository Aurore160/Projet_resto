<template>
  <div class="bar-chart-wrapper">
    <canvas ref="canvasRef"></canvas>
    <div class="bar-legend" v-if="legendItems.length">
      <div class="legend-item" v-for="(it, idx) in legendItems" :key="idx">
        <span class="legend-swatch" :style="{ background: it.color }"></span>
        <span class="legend-label">{{ it.label }}</span>
        <span class="legend-value">{{ it.value }}</span>
      </div>
    </div>
    <div class="bar-detail" v-if="hoveredIndex !== null">
      <strong>{{ labels[hoveredIndex] }}</strong>
      <div v-for="(ds, i) in datasets" :key="i" class="detail-line">
        <span class="dot" :style="{ background: ds.backgroundColor || ds.color }"></span>
        <span class="detail-label">{{ ds.label }}:</span>
        <span class="detail-value">{{ formatCurrency(ds.data[hoveredIndex]) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import {
  Chart,
  BarController,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
  Title
} from 'chart.js'
import ChartDataLabels from 'chartjs-plugin-datalabels'

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend, Title, ChartDataLabels)

const props = defineProps({
  labels: { type: Array, required: true },
  datasets: { type: Array, required: true },
  title: { type: String, default: '' }
})

const canvasRef = ref(null)
let chart = null
const hoveredIndex = ref(null)
const legendItems = ref([])

const formatCurrency = (n) => `${Number(n || 0).toLocaleString('fr-FR', { maximumFractionDigits: 0 })} FC`

const createChart = () => {
  if (!canvasRef.value) return
  const ctx = canvasRef.value.getContext('2d')
  chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: props.labels,
      datasets: props.datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        title: { display: !!props.title, text: props.title },
        // disable Chart.js built-in legend - we render a custom HTML legend
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => {
              const label = context.dataset.label || ''
              const value = context.parsed.y ?? context.parsed
              return `${label}: ${formatCurrency(value)}`
            }
          }
        },
        datalabels: { display: false }
      },
      scales: {
        x: { stacked: false, grid: { display: false } },
        y: { beginAtZero: true }
      },
      onHover: (evt, active) => {
        if (active && active.length) {
          hoveredIndex.value = active[0].index
        } else {
          hoveredIndex.value = null
        }
      }
    }
  })

  // build simple HTML legend items from datasets
  legendItems.value = props.datasets.map((ds) => ({
    label: ds.label || '',
    value: ds.data.reduce((a, b) => a + (Number(b) || 0), 0),
    color: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[0] : (ds.backgroundColor || ds.color || '#000')
  }))
}

const updateChart = () => {
  if (!chart) return
  chart.data.labels = props.labels
  chart.data.datasets = props.datasets
  chart.update()
  // update HTML legend when datasets change
  legendItems.value = props.datasets.map((ds) => ({
    label: ds.label || '',
    value: ds.data.reduce((a, b) => a + (Number(b) || 0), 0),
    color: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[0] : (ds.backgroundColor || ds.color || '#000')
  }))
}

onMounted(() => createChart())
onBeforeUnmount(() => { if (chart) { chart.destroy(); chart = null } })

watch(() => [props.labels, props.datasets], updateChart, { deep: true })
</script>

<style scoped>
.bar-chart-wrapper{ position: relative; width: 100%; height: 320px }
.bar-legend{ display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; align-items:center }
.bar-legend .legend-item{ display:flex; align-items:center; gap:8px; background: rgba(0,0,0,0.03); padding:6px 10px; border-radius:8px }
.bar-legend .legend-swatch{ width:14px; height:14px; border-radius:4px }
.bar-legend .legend-label{ font-weight:600 }
.bar-legend .legend-value{ margin-left:6px; color:#4a5568; font-weight:700 }
.bar-detail{ margin-top: 10px; background: rgba(0,0,0,0.03); padding: 8px; border-radius: 8px; display: inline-block }
.detail-line{ display:flex; align-items:center; gap:8px; margin-top:4px }
.dot{ width:12px; height:12px; border-radius:3px; display:inline-block }
.detail-label{ font-weight:600 }
.detail-value{ margin-left:6px; color:#2d3748 }
</style>
