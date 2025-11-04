<template>
  <div class="pie-chart-wrapper">
    <canvas ref="canvasRef"></canvas>
    <div class="pie-legend" v-if="legendItems.length">
      <div class="legend-item" v-for="(it, idx) in legendItems" :key="idx">
        <span class="legend-swatch" :style="{ background: it.color }"></span>
        <span class="legend-label">{{ it.label }}</span>
        <span class="legend-value">{{ it.value }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { Chart, ArcElement, PieController, Tooltip, Legend, Title } from 'chart.js'
import ChartDataLabels from 'chartjs-plugin-datalabels'

Chart.register(PieController, ArcElement, Tooltip, Legend, Title, ChartDataLabels)

const props = defineProps({
  labels: { type: Array, required: true },
  values: { type: Array, required: true },
  title: { type: String, default: '' },
  colors: { type: Array, default: () => [] } // optionnel : couleurs
})

const canvasRef = ref(null)
let chartInstance = null

const legendItems = ref([])

const buildDataset = () => {
  const palette = props.colors.length ? props.colors : [
    '#2f8be6', '#e94b3c', '#19a974', '#8a9698', '#f6a21b', '#6a4bd8', '#f06292'
  ]
  return {
    data: props.values,
    backgroundColor: palette.slice(0, props.labels.length),
    borderColor: 'transparent',
    borderWidth: 0
  }
}

const createChart = () => {
  if (!canvasRef.value) return
  const ctx = canvasRef.value.getContext('2d')
  chartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: props.labels,
      datasets: [ buildDataset() ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        title: {
          display: !!props.title,
          text: props.title,
          padding: { top: 6, bottom: 8 },
          font: { size: 14 }
        },
        // Keep Chart.js legend disabled (we render a custom HTML legend below)
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: (context) => {
              const label = context.label || ''
              const value = context.parsed ?? context.raw
              const total = context.chart.data.datasets[0].data.reduce((a,b)=>a+b,0)
              const pct = total ? ` (${Math.round((value/total)*100)}%)` : ''
              return `${label}: ${value}${pct}`
            }
          }
        },
        datalabels: {
          color: '#222',
          formatter: (value, context) => {
            const label = context.chart.data.labels[context.dataIndex] || ''
            return `${label}, ${value}`
          },
          anchor: 'end',
          align: 'end',
          offset: 12,
          font: { weight: '500' }
        }
      }
    }
  })

  // Build a simple HTML legend from labels and colors
  if (chartInstance) {
    const ds = chartInstance.data.datasets[0]
    legendItems.value = chartInstance.data.labels.map((label, i) => ({
      label,
      value: ds.data[i],
      color: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[i] : ds.backgroundColor
    }))
  }
}

const updateChart = () => {
  if (!chartInstance) return
  chartInstance.data.labels = props.labels
  chartInstance.data.datasets[0] = buildDataset()
  chartInstance.update()
  // update legend items
  const ds = chartInstance.data.datasets[0]
  legendItems.value = chartInstance.data.labels.map((label, i) => ({
    label,
    value: ds.data[i],
    color: Array.isArray(ds.backgroundColor) ? ds.backgroundColor[i] : ds.backgroundColor
  }))
}

onMounted(() => createChart())

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})

// watch props to update chart when data change
watch(() => [props.labels, props.values], updateChart, { deep: true })

// expose legendItems for the template
const __returned = { props, canvasRef, legendItems }
</script>

<style scoped>
.pie-chart-wrapper{
  width: 100%;
  height: 320px; /* ajuste si besoin */
  max-width: 100%;
  margin: 0 auto;
  position: relative;
}

.pie-legend{
  display: flex;
  flex-wrap: wrap;
  gap: 8px 12px;
  margin-top: 10px;
  align-items: center;
  justify-content: flex-start;
}
.pie-legend .legend-item{
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(0,0,0,0.03);
  padding: 6px 10px;
  border-radius: 8px;
  font-size: 0.85rem;
}
.legend-swatch{
  width: 14px;
  height: 14px;
  border-radius: 4px;
  display: inline-block;
}
.legend-label{
  color: #2d3748;
  font-weight: 600;
}
.legend-value{
  margin-left: 6px;
  color: #4a5568;
  font-weight: 700;
}
</style>
