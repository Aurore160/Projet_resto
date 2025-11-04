<template>
	<div class="statistiques-view">
		<div class="d-flex justify-content-between align-items-center mb-4">
			<h2 class="page-title"></h2>
		</div>

		<!-- KPI cards with larger gauges -->
		<div class="stats-cards">
			<div class="kpi-card" v-for="card in kpis" :key="card.key">
				<div class="kpi-header">
					<h3>{{ card.titre }}</h3>
				</div>
				<div class="kpi-body">
					<Gauge :value="card.valeur" :max="card.max || globalMax" :size="120" :label="card.label" :animateSequence="true" />
					<div class="kpi-text">
						<div class="kpi-number">{{ card.valeurDisplay }}</div>
						<div class="kpi-sub">{{ card.sublabel }}</div>
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
						<h4> Top Performants</h4>
						<ul class="insight-list">
							<li v-for="p in topPlats" :key="p.nom">
								<span class="plat-name">{{ p.nom }}</span>
								<span class="plat-count">{{ p.commandes }} commandes</span>
							</li>
						</ul>
					</div>
					<div class="insight-section">
						<h4> Points d'Attention</h4>
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

// KPI sample data tuned for statistical view
const globalMax = 1000 // used as a base for gauges when applicable

const kpis = ref([
	{ key: 'revenue', titre: 'Chiffre d\'affaires (Mois)', valeur: 3200000, valeurDisplay: '3 200 000 FC', sublabel: 'CA ce mois', label: 'CA', max: 5000000 },
	{ key: 'newClients', titre: 'Nouveaux clients', valeur: 128, valeurDisplay: '128', sublabel: 'nouveaux ce mois', label: 'Clients', max: 500 },
	{ key: 'orders', titre: 'Commandes', valeur: 425, valeurDisplay: '425', sublabel: 'commandes ce mois', label: 'Cmds', max: 2000 },
	{ key: 'conversion', titre: 'Taux de conversion', valeur: 37, valeurDisplay: '37%', sublabel: 'visites → commandes', label: 'Conv', max: 100 }
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

// charts
onMounted(() => {
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
	/* background: #f8fafc; */
	min-height: 100vh;
}

.page-title {
	font-size: 2.0rem;
	font-weight: 700;
	color: #1f2937;
	margin: 0;
}

/* KPI Cards agrandies */
.stats-cards {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 1.5rem;
	margin-bottom: 2.5rem;
}

.kpi-card {
	background: white;
	border-radius: 16px;
	padding: 1.5rem;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
	border: 1px solid #e5e7eb;
	transition: all 0.3s ease;
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
}

.kpi-body {
	display: flex;
	align-items: center;
	gap: 1.2rem;
}

.kpi-text {
	flex: 1;
}

.kpi-number {
	font-weight: 700;
	font-size: 1.8rem;
	color: #1f2937;
	line-height: 1.2;
	margin-bottom: 0.5rem;
}

.kpi-sub {
	color: #6b7280;
	font-size: 1rem;
	line-height: 1.4;
	font-weight: 500;
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
	height: 320px; /* Hauteur agrandie de 30% */
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
	display: flex;
	align-items: center;
	gap: 0.5rem;
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
	
	.kpi-number {
		font-size: 1.6rem;
	}
}

@media (max-width: 480px) {
	.statistiques-view {
		padding: 1rem;
	}
	
	.page-title {
		font-size: 1.5rem;
	}
	
	.kpi-body {
		flex-direction: column;
		text-align: center;
		gap: 1rem;
	}
	
	.chart-container {
		height: 250px;
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