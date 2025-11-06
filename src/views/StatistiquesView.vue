<template>
	<div class="statistiques-view">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h2 class="page-title">Tableau de Bord Statistiques</h2>
			<button @click="exportToPDF" class="btn-export">
				Exporter en PDF
			</button>
		</div>

		<!-- KPI cards avec jauges améliorées -->
		<div class="stats-cards">
			<div class="kpi-card" v-for="card in kpis" :key="card.key">
				<div class="kpi-header">
					<h3>{{ card.titre }}</h3>
				</div>
				<div class="kpi-body">
					<!-- Jauge centrée -->
					<div class="gauge-container">
						<Gauge 
							:value="card.valeur" 
							:max="card.max || globalMax" 
							:size="120" 
							:label="card.label" 
							:animateSequence="true" 
						/>
					</div>
					<!-- Chiffres descriptifs en colonne en dessous -->
					<div class="kpi-info-column">
						<div class="info-item">
							<div class="info-label">Valeur actuelle</div>
							<div class="info-value">{{ card.valeurDisplay }}</div>
						</div>
						<div class="info-item">
							<div class="info-label">Objectif</div>
							<div class="info-value">{{ card.maxDisplay }}</div>
						</div>
						<div class="info-item">
							<div class="info-label">Progression</div>
							<div class="info-value progress-value">{{ card.percentage }}%</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Grille de graphiques agrandis -->
		<div class="charts-grid">
			<!-- Distribution (pie) -->
			<div class="chart-card">
				<div class="chart-header">
					<h3>Répartition du chiffre d'affaires par catégorie</h3>
				</div>
				<div class="chart-container">
					<canvas id="caPie"></canvas>
				</div>
			</div>

			<!-- Evolution CA (line) -->
			<div class="chart-card">
				<div class="chart-header">
					<h3>Évolution du chiffre d'affaires (12 mois)</h3>
				</div>
				<div class="chart-container">
					<canvas id="caLine"></canvas>
				</div>
			</div>

			<!-- Commandes par semaine (bar) -->
			<div class="chart-card">
				<div class="chart-header">
					<h3>Commandes - Dernières 4 semaines</h3>
				</div>
				<div class="chart-container">
					<canvas id="ordersBar"></canvas>
				</div>
			</div>

			<!-- Top opportunités / plats faibles -->
			<div class="chart-card insights-card">
				<div class="chart-header">
					<h3>Analyses & Recommandations</h3>
				</div>
				<div class="insights-content">
					<div class="insight-section">
						<h4>Top Performants</h4>
						<ul class="insight-list">
							<li v-for="p in topPlats" :key="p.nom">
								<span class="plat-name">{{ p.nom }}</span>
								<span class="plat-count">{{ p.commandes }} commandes</span>
							</li>
						</ul>
					</div>
					<div class="insight-section">
						<h4>Points d'Attention</h4>
						<ul class="insight-list">
							<li v-for="w in weakPoints" :key="w">{{ w }}</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue'
import Chart from 'chart.js/auto'
import Gauge from '../components/Gauge.vue'
import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'

// KPI sample data tuned for statistical view
const globalMax = 1000 // used as a base for gauges when applicable

const kpis = ref([
	{ 
		key: 'revenue', 
		titre: 'Chiffre d\'affaires (Mois)', 
		valeur: 3000000, 
		valeurDisplay: '3,000,000 FC', 
		max: 5000000,
		maxDisplay: '5,000,000 FC',
		label: 'CA',
		percentage: computed(() => Math.round((3000000 / 5000000) * 100))
	},
	{ 
		key: 'newClients', 
		titre: 'Nouveaux clients', 
		valeur: 128, 
		valeurDisplay: '128', 
		max: 500,
		maxDisplay: '500',
		label: 'Clients',
		percentage: computed(() => Math.round((128 / 500) * 100))
	},
	{ 
		key: 'orders', 
		titre: 'Commandes', 
		valeur: 425, 
		valeurDisplay: '425', 
		max: 2000,
		maxDisplay: '2,000',
		label: 'Cmds',
		percentage: computed(() => Math.round((425 / 2000) * 100))
	},
	{ 
		key: 'conversion', 
		titre: 'Taux de conversion', 
		valeur: 37, 
		valeurDisplay: '37%', 
		max: 100,
		maxDisplay: '100%',
		label: 'Conv',
		percentage: 37
	}
])

const topPlats = ref([
	{ nom: 'Poulet Grillé', commandes: 520 },
	{ nom: 'Frites Maison', commandes: 480 },
	{ nom: 'Pizza Margherita', commandes: 430 },
	{ nom: 'Burger Spécial', commandes: 390 }
])

const weakPoints = ref([
	'Plat X — Marge faible à améliorer',
	'Heure creuse 15h-17h sous-exploitée',
	'Fidélisation clients <30 jours faible'
])

// Export PDF
const exportToPDF = async () => {
	try {
		const element = document.querySelector('.statistiques-view')
		const canvas = await html2canvas(element, {
			scale: 2,
			useCORS: true,
			allowTaint: true
		})
		
		const imgData = canvas.toDataURL('image/png')
		const pdf = new jsPDF('p', 'mm', 'a4')
		const imgWidth = 210
		const pageHeight = 295
		const imgHeight = (canvas.height * imgWidth) / canvas.width
		let heightLeft = imgHeight
		
		let position = 0
		
		pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
		heightLeft -= pageHeight
		
		while (heightLeft >= 0) {
			position = heightLeft - imgHeight
			pdf.addPage()
			pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight)
			heightLeft -= pageHeight
		}
		
		pdf.save(`statistiques-${new Date().toISOString().split('T')[0]}.pdf`)
	} catch (error) {
		console.error('Erreur export PDF:', error)
		alert('Erreur lors de l\'export PDF')
	}
}

// Backend integration example
const fetchStatsData = async () => {
	try {
		// Exemple d'appel API - à adapter selon votre backend
		/*
		const response = await fetch('/api/statistics', {
			headers: {
				'Authorization': `Bearer ${yourToken}`
			}
		})
		const data = await response.json()
		
		// Mettre à jour les KPI
		kpis.value = data.kpis.map(kpi => ({
			...kpi,
			percentage: Math.round((kpi.valeur / kpi.max) * 100)
		}))
		
		topPlats.value = data.topPlats
		weakPoints.value = data.weakPoints
		*/
	} catch (error) {
		console.error('Erreur chargement données:', error)
	}
}

// charts
onMounted(() => {
	fetchStatsData()
	
	// pie CA par catégorie - version agrandie
	const ctxPie = document.getElementById('caPie')
	new Chart(ctxPie, {
		type: 'pie',
		data: {
			labels: ['Boissons', 'Plats', 'Desserts', 'Autres'],
			datasets: [{ 
				data: [25, 55, 12, 8], 
				backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#9CA3AF'],
				borderWidth: 3,
				borderColor: '#fff'
			}]
		},
		options: { 
			responsive: true,
			maintainAspectRatio: false,
			plugins: { 
				legend: { 
					position: 'right',
					labels: {
						boxWidth: 16,
						padding: 20,
						font: { 
							size: 14,
							weight: '500'
						}
					}
				},
				tooltip: {
					bodyFont: { size: 14 },
					titleFont: { size: 14 }
				}
			}
		}
	})

	// line CA 12 mois - version agrandie
	const ctxLine = document.getElementById('caLine')
	new Chart(ctxLine, {
		type: 'line',
		data: {
			labels: ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
			datasets: [{ 
				label: 'CA', 
				data: [1200000,900000,1100000,1500000,1800000,2100000,2400000,2600000,2300000,2500000,2700000,3000000], 
				borderColor: '#3B82F6', 
				backgroundColor: 'rgba(59,130,246,0.12)', 
				fill: true, 
				tension: 0.3,
				borderWidth: 3
			}]
		},
		options: { 
			responsive: true,
			maintainAspectRatio: false,
			plugins: { 
				legend: { display: false } 
			},
			scales: {
				x: {
					grid: { display: false },
					ticks: { 
						font: { 
							size: 13,
							weight: '500'
						}
					}
				},
				y: {
					ticks: { 
						font: { 
							size: 13,
							weight: '500'
						},
						callback: function(value) {
							return (value / 1000000).toFixed(1) + 'M FC'
						}
					},
					grid: { color: 'rgba(0,0,0,0.08)' }
				}
			}
		}
	})

	// bar orders last 4 weeks - version agrandie
	const ctxBar = document.getElementById('ordersBar')
	new Chart(ctxBar, {
		type: 'bar',
		data: { 
			labels: ['Semaine 1', 'Semaine 2', 'Semaine 3', 'Semaine 4'], 
			datasets: [{ 
				label: 'Commandes', 
				data: [480, 520, 610, 700], 
				backgroundColor: ['#10B981','#3B82F6','#F59E0B','#EF4444'],
				borderRadius: 6,
				borderSkipped: false
			}] 
		},
		options: { 
			responsive: true,
			maintainAspectRatio: false,
			plugins: { 
				legend: { display: false }
			},
			scales: {
				x: {
					grid: { display: false },
					ticks: { 
						font: { 
							size: 13,
							weight: '500'
						}
					}
				},
				y: {
					ticks: { 
						font: { 
							size: 13,
							weight: '500'
						}
					},
					grid: { color: 'rgba(0,0,0,0.08)' }
				}
			}
		}
	})
})
</script>

<style scoped>
.statistiques-view {
	padding: 2rem;
	min-height: 100vh;
}

.page-title {
	font-size: 2.0rem;
	font-weight: 700;
	color: #1f2937;
	margin: 0;
}

.btn-export {
	background: #e5d8bb;
	color: black;
	border: none;
	padding: 0.75rem 1.5rem;
	border-radius: 8px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s ease;
}

.btn-export:hover {
	background: #dcca9b;
	transform: translateY(-2px);
	color:#ffffff;
}

/* KPI Cards améliorées */
.stats-cards {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
	gap: 1.1rem;
	margin-bottom: 2.4rem;
}

.kpi-card {
	background: white;
	border-radius: 16px;
	padding: 1.5rem;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
	border: 1px solid #e5e7eb;
	transition: all 0.3s ease;
	display: flex;
	flex-direction: column;
}

.kpi-card:hover {
	transform: translateY(-3px);
	box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.kpi-header h3 {
	font-size: 1.3rem;
	font-weight: 600;
	color: #374151;
	margin: 0 0 1.2rem 0;
	text-align: center;
}

.kpi-body {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 1.2rem;
	flex: 1;
}

.gauge-container {
	display: flex;
	justify-content: center;
	align-items: center;
	height: 140px;
}

/* Colonne d'informations sous la jauge */
.kpi-info-column {
	display: flex;
	flex-direction: column;
	width: 100%;
	gap: 0.8rem;
}

.info-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 0.6rem 0;
	border-bottom: 1px solid #f3f4f6;
}

.info-item:last-child {
	border-bottom: none;
}

.info-label {
	font-size: 0.9rem;
	color: #6b7280;
	font-weight: 500;
}

.info-value {
	font-size: 1rem;
	font-weight: 600;
	color: #1f2937;
}

.progress-value {
	color: #10B981;
}

/* Grille de graphiques agrandis */
.charts-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
	gap: 2rem;
}

.chart-card {
	background: white;
	border-radius: 16px;
	padding: 1.8rem;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
	border: 1px solid #e5e7eb;
	transition: all 0.3s ease;
	min-height: 420px;
	display: flex;
	flex-direction: column;
}

.chart-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.chart-header {
	margin-bottom: 1.5rem;
}

.chart-header h3 {
	font-size: 1.4rem;
	font-weight: 600;
	color: #374151;
	margin: 0;
	line-height: 1.3;
}

.chart-container {
	position: relative;
	height: 320px;
	width: 100%;
	flex: 1;
}

/* Carte d'analyses agrandie */
.insights-card {
	display: flex;
	flex-direction: column;
}

.insights-content {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 1.5rem;
}

.insight-section h4 {
	font-size: 1.2rem;
	font-weight: 600;
	color: #374151;
	margin: 0 0 1rem 0;
}

.insight-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.insight-list li {
	padding: 0.8rem 0;
	border-bottom: 1px solid #f3f4f6;
	font-size: 1rem;
	color: #4b5563;
	display: flex;
	justify-content: space-between;
	align-items: center;
	line-height: 1.4;
}

.insight-list li:last-child {
	border-bottom: none;
}

.plat-name {
	font-weight: 500;
	font-size: 1rem;
}

.plat-count {
	font-weight: 600;
	color: #059669;
	background: #d1fae5;
	padding: 0.4rem 0.8rem;
	border-radius: 16px;
	font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 1200px) {
	.charts-grid {
		grid-template-columns: 1fr;
	}
	
	.stats-cards {
		grid-template-columns: repeat(2, 1fr);
	}
}

@media (max-width: 768px) {
	.statistiques-view {
		padding: 1.5rem;
	}
	
	.page-title {
		font-size: 1.8rem;
	}
	
	.stats-cards {
		grid-template-columns: 1fr;
		gap: 1.2rem;
	}
	
	.kpi-card {
		padding: 1.2rem;
	}
	
	.chart-card {
		padding: 1.5rem;
		min-height: 380px;
	}
	
	.chart-container {
		height: 280px;
	}
	
	.charts-grid {
		grid-template-columns: 1fr;
		gap: 1.5rem;
	}
	
	.kpi-header h3,
	.chart-header h3 {
		font-size: 1.2rem;
	}
}

@media (max-width: 480px) {
	.statistiques-view {
		padding: 1rem;
	}
	
	.page-title {
		font-size: 1.5rem;
	}
	
	.chart-container {
		height: 250px;
	}
	
	.kpi-info-column {
		gap: 0.6rem;
	}
	
	.info-item {
		padding: 0.5rem 0;
	}
	
	.info-label {
		font-size: 0.85rem;
	}
	
	.info-value {
		font-size: 0.9rem;
	}
}

/* Animation subtile */
.kpi-card, .chart-card {
	animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
	from {
		opacity: 0;
		transform: translateY(15px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}
</style>