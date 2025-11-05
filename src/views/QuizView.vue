<template>
  <div class="quiz-page">
    <HeaderSpecified />
    <section class="quiz-main container">
      <h2>Quiz Time</h2>
      <div v-if="!done">
        <div class="q-card">
          <h3>Question {{ idx+1 }} / {{ questions.length }}</h3>
          <p>{{ question.q }}</p>
          <ul class="answers">
            <li v-for="(a,i) in question.answers" :key="i">
              <button @click="choose(i)">{{ a }}</button>
            </li>
          </ul>
        </div>
      </div>

      <div v-else>
        <h3>Résultat</h3>
        <p>Points gagnés cette session: {{ gained }}</p>
        <p>Total points: {{ store.state.points }} pts</p>
      </div>
    </section>
    <FooterSpecified />
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import {useUserStore} from "@/stores/userStore.js";
const useStore = useUserStore();

const questions = [
  { q:"Quel ingrédient principal dans un burger classique ?", answers:["Poulet","Boeuf","Saumon","Tofu"], correct:1, points:20 },
  { q:"Quel fromage pour un burger classique ?", answers:["Cheddar","Brie","Feta","Bleu"], correct:0, points:10 },
  { q:"Quel ingrédient est sucré ?", answers:["Tomate","Oignon","Miel","Laitue"], correct:2, points:10 },
  { q:"Quelle boisson est généralement servie froide avec du citron ?", answers:["Café noir","Lait chaud","Thé glacé","Chocolat chaud"], correct:2, points:10 },
  { q:"Quel ingrédient est essentiel dans un smoothie ?", answers:["Riz","Fruits","Pâtes","Fromages"], correct:1, points:10 },
  { q:"Lequel de ces plats est souvent considéré comme un plat principal pour le dîner ?", answers:["Crêpes sucrées","Soupe à l'oignon","Gâteau au chocolat","Céréales"], correct:1, points:10 },
  { q:"Quel accompagnement va le plus souvent avec un steak ?", answers:["Frites","Muffin","Pancakes","Tarte aux pommes"], correct:0, points:10 },
  { q:"Quel aliment est typiquement servi au petit-déjeuner ?", answers:["Omelette","Poulet rôti","Pizza","Lasagne"], correct:0, points:5 },
  { q:"Le foufou est une pâte faite à base de ?", answers:["Riz","Farine de blé","Haricots rouges","Manioc, Maïs"], correct:3, points:10 },
  { q:"Le liboke est un plat cuit", answers:["A la vapeur dans des feuilles de bananier","A la poêle","Dans une marmite en terre","Au four"], correct:0, points:10 },
  { q:"Quelle huile est la plus utilisée dans la cuisine congolaise ?", answers:["Huile de tournesol","Huile de palme","Huile d'olive","Huile de sésame"], correct:1, points:10 },
  
];

const idx = ref(0);
const gained = ref(0);
const done = ref(false);
const question = computed(() => questions[idx.value]);

function choose(i) {
  if (i === question.value.correct) {
    gained.value += question.value.points;
    alert(`Bonne réponse +${question.value.points} pts`);
  } else alert("Mauvaise réponse");
  idx.value++;
  if (idx.value >= questions.length) {
    
    store.addPoints(gained.value);
    done.value = true;
  }
}
</script>

<style scoped>


.quiz-main{ 
    padding:24px 16px; 
    max-width:900px; 
    margin:24px auto; 
}

.q-card{ 
    background:#cfbd97; 
    padding:18px; 
    border-radius:12px; 
}

.answers{ 
    list-style:none; 
    padding:0; 
    margin-top:12px; 
    display:grid; 
    gap:8px 
}

.answers button{ 
    padding:10px; 
    border-radius:8px; 
    background:#fff; 
    border:1px solid #ddd; 
    cursor:pointer; 
    text-align:left; 
}
</style>