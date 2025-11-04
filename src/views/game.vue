<template>
    <div class="game-view container py-4">
        <div class="row justify-content-center mt-3">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark text-center py-3">
                        <h2 class="mb-0">🎮 Jeu du Chef Pressé 🍽️</h2>
                    </div>
                    <div class="card-body text-center p-4">
                        <!-- Écran de configuration -->
                        <div v-if="gameState === 'config'" class="config-screen">
                            <h3 class="text-primary mb-4">Configuration</h3>
                            
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">Plat</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <button 
                                                    v-for="recipe in recipes" 
                                                    :key="recipe.name"
                                                    @click="selectedRecipe = recipe.name"
                                                    class="btn"
                                                    :class="selectedRecipe === recipe.name ? 'btn-success' : 'btn-outline-success'"
                                                >
                                                    {{ recipe.emoji }} {{ recipe.name }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">Difficulté</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-grid gap-2">
                                                <button 
                                                    v-for="diff in difficulties" 
                                                    :key="diff.level"
                                                    @click="selectedDifficulty = diff.level"
                                                    class="btn"
                                                    :class="selectedDifficulty === diff.level ? 
                                                        diff.class : 'btn-outline-secondary'"
                                                >
                                                    {{ diff.emoji }} {{ diff.name }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Recette: {{ getCurrentRecipe().name }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h6 class="text-success">✅ À attraper:</h6>
                                            <div class="d-flex flex-wrap justify-content-center gap-2 mb-2">
                                                <div 
                                                    v-for="ing in getCurrentRecipe().goodIngredients" 
                                                    :key="ing"
                                                    class="ingredient-preview"
                                                    :class="ing"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <h6 class="text-danger">❌ À éviter:</h6>
                                            <div class="d-flex flex-wrap justify-content-center gap-2 mb-2">
                                                <div 
                                                    v-for="ing in getCurrentRecipe().badIngredients" 
                                                    :key="ing"
                                                    class="ingredient-preview bad"
                                                    :class="ing"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button @click="startGame" class="btn btn-success btn-lg px-5">
                                Commencer à Jouer
                            </button>
                        </div>

                        <!-- Écran d'accueil -->
                        <div v-if="gameState === 'menu'" class="text-center">
                            <div class="mb-4">
                                <h3 class="text-success mb-3">Deviens le Meilleur Chef !</h3>
                                <p class="lead">Attrape les bons ingrédients et évite les intrus</p>
                            </div>

                            <!-- Tableau des scores -->
                            <div class="card mb-4">
                                <div class="card-header bg-warning">
                                    <h4 class="mb-0">🏆 Meilleurs Scores</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Joueur</th>
                                                    <th>Score</th>
                                                    <th>Plat</th>
                                                    <th>Niveau</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(score, index) in highScores" :key="score.id">
                                                    <td>{{ index + 1 }}</td>
                                                    <td>{{ score.player }}</td>
                                                    <td><strong>{{ score.score }}</strong></td>
                                                    <td>{{ score.recipe }}</td>
                                                    <td>
                                                        <span class="badge" :class="getDifficultyBadgeClass(score.difficulty)">
                                                            {{ getDifficultyName(score.difficulty) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr v-if="highScores.length === 0">
                                                    <td colspan="5" class="text-muted">Aucun score enregistré</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-center">
                                <button @click="gameState = 'config'" class="btn btn-success btn-lg">
                                    Nouvelle Partie
                                </button>
                                <button v-if="highScores.length > 0" @click="resetScores" class="btn btn-outline-danger">
                                    Reset Scores
                                </button>
                            </div>
                        </div>

                        <!-- Jeu en cours -->
                        <div v-if="gameState === 'playing'">
                            <div class="game-info mb-3">
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <div class="info-card">
                                            <div class="info-label">Score</div>
                                            <div class="info-value text-primary">{{ score }}</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="info-card">
                                            <div class="info-label">Vies</div>
                                            <div class="info-value text-danger">{{ lives }}</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="info-card">
                                            <div class="info-label">Temps</div>
                                            <div class="info-value text-warning">{{ timeLeft }}s</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="game-container" 
                                 :class="getCurrentDifficulty().level"
                                 @mousemove="moveChef"
                                 @touchmove="moveChefTouch"
                                 @touchstart="handleTouchStart">
                                
                                <!-- Chef avec mouvement libre -->
                                <div class="chef" :style="{
                                    left: chefPosition.x + 'px', 
                                    top: chefPosition.y + 'px'
                                }"></div>

                                <!-- Ingrédients qui tombent -->
                                <div
                                    v-for="ingredient in ingredients"
                                    :key="ingredient.id"
                                    class="ingredient"
                                    :class="[ingredient.type, ingredient.isGood ? 'good' : 'bad']"
                                    :style="{
                                        left: ingredient.x + 'px', 
                                        top: ingredient.y + 'px',
                                        transform: `rotate(${ingredient.rotation}deg)`
                                    }"
                                ></div>

                                <!-- Effets visuels -->
                                <div
                                    v-for="effect in effects"
                                    :key="effect.id"
                                    class="effect"
                                    :class="effect.type"
                                    :style="{
                                        left: effect.x + 'px',
                                        top: effect.y + 'px'
                                    }"
                                ></div>
                            </div>

                            <div class="mt-3">
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <small class="text-success">
                                            ✅ {{ getCurrentRecipe().goodIngredients.join(', ') }}
                                        </small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-danger">
                                            ❌ {{ getCurrentRecipe().badIngredients.join(', ') }}
                                        </small>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Déplace le chef avec la souris | 
                                    <span class="text-success">+{{ getCurrentDifficulty().points }} pts</span> |
                                    <span class="text-danger">-1 vie</span>
                                </small>
                            </div>
                        </div>

                        <!-- Game Over -->
                        <div v-if="gameState === 'gameOver'" class="game-over-screen">
                            <div class="text-center p-4">
                                <h3 class="text-warning mb-3">Partie Terminée !</h3>
                                
                                <div class="score-result mb-4">
                                    <div class="final-score display-6 text-success mb-2">{{ score }}</div>
                                    <div class="text-muted">Points marqués</div>
                                </div>

                                <div v-if="isNewHighScore" class="alert alert-success mb-4">
                                    <h5>🎉 Nouveau Record !</h5>
                                    <p>Entre ton nom :</p>
                                    <input v-model="playerName" 
                                           type="text" 
                                           class="form-control text-center" 
                                           placeholder="Ton nom"
                                           maxlength="15">
                                </div>
                                <div v-else class="alert alert-info mb-4">
                                    Meilleur score: {{ Math.max(...highScores.map(s => s.score), 0) }}
                                </div>

                                <div class="d-flex gap-2 justify-content-center">
                                    <button @click="saveScoreAndRestart" class="btn btn-success">
                                        Rejouer
                                    </button>
                                    <button @click="goToMenu" class="btn btn-outline-primary">
                                        Menu
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contrôles audio -->
        <div class="audio-controls fixed-bottom m-3">
            <button @click="toggleSound" class="btn btn-sm btn-outline-secondary">
                {{ soundEnabled ? '🔊 Son' : '🔇 Muet' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// États du jeu
const gameState = ref('menu')
const score = ref(0)
const lives = ref(3)
const timeLeft = ref(60)
const playerName = ref('')
const isNewHighScore = ref(false)

// Configuration
const selectedRecipe = ref('burger')
const selectedDifficulty = ref('medium')
const soundEnabled = ref(true)

// Éléments du jeu - MOUVEMENT 2D
const chefPosition = ref({ x: 300, y: 400 })
const ingredients = ref([])
const effects = ref([])
const highScores = ref([])

// Intervalles
let gameInterval
let ingredientInterval
let timerInterval

// Sons
const audioContext = ref(null)
const sounds = ref({})

// Recettes disponibles
const recipes = ref([
    {
        name: 'Burger',
        emoji: '🍔',
        goodIngredients: ['tomato', 'steak', 'lettuce', 'cheese', 'onion'],
        badIngredients: ['fish', 'broccoli', 'eggplant']
    },
    {
        name: 'Pizza',
        emoji: '🍕',
        goodIngredients: ['tomato', 'cheese', 'mushroom', 'pepperoni', 'olive'],
        badIngredients: ['fish', 'broccoli', 'onion']
    },
    {
        name: 'Salade',
        emoji: '🥗',
        goodIngredients: ['lettuce', 'tomato', 'onion', 'carrot', 'olive'],
        badIngredients: ['steak', 'fish', 'cheese']
    }
])

// Niveaux de difficulté
const difficulties = ref([
    { 
        level: 'easy', 
        name: 'Facile', 
        emoji: '😊', 
        class: 'btn-success', 
        speed: 1.5, 
        spawnRate: 1200, 
        points: 5, 
        lives: 5
    },
    { 
        level: 'medium', 
        name: 'Moyen', 
        emoji: '😐', 
        class: 'btn-warning', 
        speed: 2.5, 
        spawnRate: 900, 
        points: 10, 
        lives: 3
    },
    { 
        level: 'hard', 
        name: 'Difficile', 
        emoji: '😰', 
        class: 'btn-danger', 
        speed: 3.5, 
        spawnRate: 600, 
        points: 15, 
        lives: 2
    }
])

// Types d'ingrédients
const ingredientTypes = ref({
    tomato: { points: 10, isGood: true },
    steak: { points: 10, isGood: true },
    lettuce: { points: 10, isGood: true },
    cheese: { points: 10, isGood: true },
    onion: { points: 10, isGood: true },
    mushroom: { points: 10, isGood: true },
    pepperoni: { points: 10, isGood: true },
    olive: { points: 10, isGood: true },
    carrot: { points: 10, isGood: true },
    fish: { points: -1, isGood: false },
    broccoli: { points: -1, isGood: false },
    eggplant: { points: -1, isGood: false }
})

// Initialisation
onMounted(() => {
    loadHighScores()
    initAudio()
})

// Audio
const initAudio = () => {
    try {
        audioContext.value = new (window.AudioContext || window.webkitAudioContext)()
        
        sounds.value = {
            catch: createSound(800, 0.3),
            miss: createSound(300, 0.4),
            gameOver: createSound(200, 0.5)
        }
    } catch (e) {
        soundEnabled.value = false
    }
}

const createSound = (frequency, duration) => {
    return () => {
        if (!soundEnabled.value || !audioContext.value) return
        
        const oscillator = audioContext.value.createOscillator()
        const gainNode = audioContext.value.createGain()
        
        oscillator.connect(gainNode)
        gainNode.connect(audioContext.value.destination)
        
        oscillator.frequency.value = frequency
        oscillator.type = 'sine'
        
        gainNode.gain.setValueAtTime(0.2, audioContext.value.currentTime)
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.value.currentTime + duration)
        
        oscillator.start(audioContext.value.currentTime)
        oscillator.stop(audioContext.value.currentTime + duration)
    }
}

const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value
}

// Getters
const getCurrentRecipe = () => {
    return recipes.value.find(r => r.name === selectedRecipe.value) || recipes.value[0]
}

const getCurrentDifficulty = () => {
    return difficulties.value.find(d => d.level === selectedDifficulty.value) || difficulties.value[1]
}

const getDifficultyName = (level) => {
    const diff = difficulties.value.find(d => d.level === level)
    return diff ? diff.name : 'Moyen'
}

const getDifficultyBadgeClass = (level) => {
    const classes = {
        easy: 'bg-success',
        medium: 'bg-warning',
        hard: 'bg-danger'
    }
    return classes[level] || 'bg-secondary'
}

// Scores
const loadHighScores = () => {
    const saved = localStorage.getItem('chefGameHighScores')
    highScores.value = saved ? JSON.parse(saved) : []
}

const saveHighScore = () => {
    if (!playerName.value.trim()) {
        playerName.value = 'Joueur'
    }
    
    const newScore = {
        id: Date.now(),
        player: playerName.value.substring(0, 15),
        score: score.value,
        recipe: getCurrentRecipe().name,
        difficulty: selectedDifficulty.value,
        date: new Date().toLocaleDateString()
    }
    
    highScores.value.push(newScore)
    highScores.value.sort((a, b) => b.score - a.score)
    highScores.value = highScores.value.slice(0, 10)
    
    localStorage.setItem('chefGameHighScores', JSON.stringify(highScores.value))
}

const resetScores = () => {
    if (confirm('Réinitialiser tous les scores ?')) {
        highScores.value = []
        localStorage.removeItem('chefGameHighScores')
    }
}

// Jeu principal
const startGame = () => {
    gameState.value = 'playing'
    score.value = 0
    lives.value = getCurrentDifficulty().lives
    timeLeft.value = 60
    ingredients.value = []
    effects.value = []
    isNewHighScore.value = false
    playerName.value = ''
    chefPosition.value = { x: 300, y: 400 }

    const difficulty = getCurrentDifficulty()

    clearIntervals()

    gameInterval = setInterval(updateGame, 16)

    ingredientInterval = setInterval(() => {
        generateIngredient()
    }, difficulty.spawnRate)

    timerInterval = setInterval(() => {
        timeLeft.value--
        if (timeLeft.value <= 0) {
            endGame()
        }
    }, 1000)
}

const clearIntervals = () => {
    clearInterval(gameInterval)
    clearInterval(ingredientInterval)
    clearInterval(timerInterval)
}

const generateIngredient = () => {
    const recipe = getCurrentRecipe()
    const allIngredients = [...recipe.goodIngredients, ...recipe.badIngredients]
    const randomType = allIngredients[Math.floor(Math.random() * allIngredients.length)]
    
    const isGood = recipe.goodIngredients.includes(randomType)
    
    const newIngredient = {
        id: Date.now() + Math.random(),
        type: randomType,
        isGood: isGood,
        points: isGood ? getCurrentDifficulty().points : -1,
        x: Math.random() * 550 + 25,
        y: -60,
        speed: getCurrentDifficulty().speed + Math.random() * 2,
        rotation: Math.random() * 360
    }
    
    ingredients.value.push(newIngredient)
}

const updateGame = () => {
    // Mettre à jour les ingrédients
    for (let i = ingredients.value.length - 1; i >= 0; i--) {
        const ingredient = ingredients.value[i]
        ingredient.y += ingredient.speed
        ingredient.rotation += 2

        // Collision avec le chef (cercle)
        const dx = ingredient.x - chefPosition.value.x
        const dy = ingredient.y - chefPosition.value.y
        const distance = Math.sqrt(dx * dx + dy * dy)
        
        if (distance < 40) {
            createEffect(ingredient.x, ingredient.y, ingredient.isGood ? 'good' : 'bad')
            
            if (ingredient.isGood) {
                score.value += Math.max(1, ingredient.points)
                if (soundEnabled.value) sounds.value.catch()
            } else {
                lives.value--
                if (soundEnabled.value) sounds.value.miss()
                if (lives.value <= 0) {
                    endGame()
                    break
                }
            }
            
            ingredients.value.splice(i, 1)
        } else if (ingredient.y > 550) {
            ingredients.value.splice(i, 1)
        }
    }

    // Mettre à jour les effets
    for (let i = effects.value.length - 1; i >= 0; i--) {
        effects.value[i].lifetime--
        if (effects.value[i].lifetime <= 0) {
            effects.value.splice(i, 1)
        }
    }
}

const createEffect = (x, y, type) => {
    effects.value.push({
        id: Date.now() + Math.random(),
        type: type,
        x: x,
        y: y,
        lifetime: 20
    })
}

// Contrôles - MOUVEMENT 2D COMPLET
const moveChef = (event) => {
    if (gameState.value !== 'playing') return
    
    const gameRect = event.currentTarget.getBoundingClientRect()
    const mouseX = event.clientX - gameRect.left
    const mouseY = event.clientY - gameRect.top
    
    // Limites du conteneur de jeu
    chefPosition.value.x = Math.max(40, Math.min(610, mouseX))
    chefPosition.value.y = Math.max(40, Math.min(460, mouseY))
}

const moveChefTouch = (event) => {
    if (gameState.value !== 'playing') return
    event.preventDefault()
    
    const touch = event.touches[0]
    const gameRect = event.currentTarget.getBoundingClientRect()
    const touchX = touch.clientX - gameRect.left
    const touchY = touch.clientY - gameRect.top
    
    chefPosition.value.x = Math.max(40, Math.min(610, touchX))
    chefPosition.value.y = Math.max(40, Math.min(460, touchY))
}

const handleTouchStart = (event) => {
    moveChefTouch(event)
}

// Fin de jeu
const endGame = () => {
    clearIntervals()
    
    const maxScore = Math.max(...highScores.value.map(s => s.score), 0)
    isNewHighScore.value = score.value > maxScore
    
    if (soundEnabled.value) sounds.value.gameOver()
    gameState.value = 'gameOver'
}

const saveScoreAndRestart = () => {
    if (isNewHighScore.value) {
        saveHighScore()
    }
    startGame()
}

const goToMenu = () => {
    if (isNewHighScore.value) {
        saveHighScore()
    }
    gameState.value = 'menu'
}

// Nettoyage
onUnmounted(() => {
    clearIntervals()
})
</script>

<style scoped>
@import url('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');

.game-container {
    position: relative;
    width: 100%;
    max-width: 600px;
    height: 400px;
    background: linear-gradient(180deg, #87CEEB 0%, #98FB98 100%);
    border: 3px solid #8b4513;
    border-radius: 15px;
    overflow: hidden;
    margin: 0 auto;
    cursor: none;
}

/* Thèmes de difficulté simples */
.game-container.easy {
    background: linear-gradient(180deg, #64b3f4 0%, #c2e59c 100%);
}

.game-container.medium {
    background: linear-gradient(180deg, #ff9a00 0%, #ff6b6b 100%);
}

.game-container.hard {
    background: linear-gradient(180deg, #8E2DE2 0%, #4A00E0 100%);
}

.chef {
    position: absolute;
    width: 60px;
    height: 60px;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="30" r="20" fill="%23FFB6C1"/><rect x="30" y="50" width="40" height="40" fill="%23FFFFFF"/><circle cx="35" cy="25" r="3" fill="%23000"/><circle cx="65" cy="25" r="3" fill="%23000"/><path d="M40 35 Q50 45 60 35" stroke="%23000" stroke-width="2" fill="none"/></svg>');
    background-size: contain;
    transition: all 0.1s ease;
    z-index: 10;
}

.ingredient {
    position: absolute;
    width: 40px;
    height: 40px;
    background-size: contain;
    background-repeat: no-repeat;
    transition: transform 0.1s;
    z-index: 5;
    animation: float 2s ease-in-out infinite;
}

.ingredient-preview {
    width: 30px;
    height: 30px;
    background-size: contain;
    background-repeat: no-repeat;
    display: inline-block;
}

.ingredient.good {
    filter: drop-shadow(2px 2px 3px rgba(0, 255, 0, 0.4));
}

.ingredient.bad {
    filter: drop-shadow(2px 2px 3px rgba(255, 0, 0, 0.4));
    animation: shake 0.8s ease-in-out infinite alternate, float 2s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-3px) rotate(2deg); }
}

@keyframes shake {
    from { transform: translateX(-2px) rotate(-2deg); }
    to { transform: translateX(2px) rotate(2deg); }
}

.effect {
    position: absolute;
    width: 50px;
    height: 50px;
    background-size: contain;
    background-repeat: no-repeat;
    pointer-events: none;
    z-index: 8;
    animation: pop 0.6s ease-out forwards;
}

.effect.good {
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="%2300FF00" stroke-width="3"/><text x="50" y="58" font-size="30" text-anchor="middle" fill="%2300FF00">+</text></svg>');
}

.effect.bad {
    background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="%23FF0000" stroke-width="3"/><text x="50" y="58" font-size="30" text-anchor="middle" fill="%23FF0000">-</text></svg>');
}

@keyframes pop {
    0% { transform: scale(0.5); opacity: 1; }
    100% { transform: scale(1.2); opacity: 0; }
}

/* Cartes d'information */
.info-card {
    padding: 8px;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}

.info-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #6c757d;
}

.info-value {
    font-size: 1.2rem;
    font-weight: bold;
}

.final-score {
    font-weight: bold;
}

.audio-controls {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 1000;
}

/* Images des ingrédients */
.ingredient.tomato, .ingredient-preview.tomato { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="%23FF4444"/><path d="M30 30 Q50 10 70 30" fill="%2344AA44"/></svg>'); }
.ingredient.steak, .ingredient-preview.steak { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="45" ry="35" fill="%238B4513"/></svg>'); }
.ingredient.lettuce, .ingredient-preview.lettuce { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M20 50 Q50 20 80 50 Q50 80 20 50" fill="%2390EE90"/></svg>'); }
.ingredient.cheese, .ingredient-preview.cheese { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M20 20 L80 20 L80 80 L20 80 Z" fill="%23FFD700"/><circle cx="40" cy="40" r="8" fill="%23FFA500"/><circle cx="60" cy="60" r="8" fill="%23FFA500"/><circle cx="40" cy="65" r="6" fill="%23FFA500"/></svg>'); }
.ingredient.onion, .ingredient-preview.onion { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="35" fill="%23800080"/><circle cx="50" cy="50" r="25" fill="%23E6E6FA"/></svg>'); }
.ingredient.fish, .ingredient-preview.fish { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="40" ry="20" fill="%23448777"/><path d="M80 50 L95 40 L95 60 Z" fill="%23448777"/><circle cx="35" cy="45" r="5" fill="%23000"/></svg>'); }
.ingredient.mushroom, .ingredient-preview.mushroom { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><ellipse cx="50" cy="35" rx="35" ry="25" fill="%23FFF"/><rect x="40" y="35" width="20" height="25" fill="%23DEB887"/></svg>'); }
.ingredient.pepperoni, .ingredient-preview.pepperoni { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="35" fill="%23CC0000"/></svg>'); }
.ingredient.olive, .ingredient-preview.olive { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="30" ry="20" fill="%23333"/></svg>'); }
.ingredient.carrot, .ingredient-preview.carrot { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M30 50 L70 30 L65 70 Z" fill="%23FF8C00"/><path d="M70 25 L85 20 L80 35 Z" fill="%2344AA44"/></svg>'); }
.ingredient.broccoli, .ingredient-preview.broccoli { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="40" r="25" fill="%23008000"/><ellipse cx="50" cy="70" rx="15" ry="20" fill="%2390EE90"/></svg>'); }
.ingredient.eggplant, .ingredient-preview.eggplant { background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><ellipse cx="50" cy="50" rx="25" ry="35" fill="%23800080"/><path d="M50 15 Q55 5 60 15" fill="%2344AA44"/></svg>'); }

/* Responsive */
@media (max-width: 768px) {
    .game-container {
        height: 350px;
    }
    
    .chef {
        width: 50px;
        height: 50px;
    }
    
    .ingredient {
        width: 35px;
        height: 35px;
    }
    
    .info-value {
        font-size: 1rem;
    }
}
</style>