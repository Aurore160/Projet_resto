<template>
    <div class="body">
        <div class="form-section">
            <h2 > Récupérer votre mot de passe </h2>
            <p>
                Entrez votre adresse mail pour recevoir un lien de réinitialisation. 
            </p>
                
            <form @submit.prevent="goToRecovery">
                <label for="email"> Adresse mail</label>
                <input type="email" id="email" v-model="email" placeholder="Entrez votre adresse mail" required />
                
                <button type="submit"> Envoyer le lien </button>
            </form>
               
            <router-link to="/" class="link"> Retour à la connexion </router-link>
               
        </div>
    </div>
    
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";

const email = ref("");
const router = useRouter();

const goToRecovery = async () => {
    if(!email.value) {
        alert("Veuillez entrer votre adresse mail ");
        return;
    }
    console.log(" Lien envoyé à :", email.value);


    router.push("/recovery");
};
</script>

<style scoped>

.body {
    background-image: url("@/assets/forgot.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

}

.form-section {
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    box-shadow: none;
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 40%;
    height: 67%;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 3rem;
    border-radius: 25px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin-right: 40px;
    float: right;
}
form{
    width: 95%;
   
}

h2 {
    text-align:center;
}

p {
    color:#cfbd97;
}
input, button {
    
    border: none;
    padding: 10px;
    border-radius: 8px;
    margin: 8px 0;
    width: 100%;
    
}

input:focus {
    outline: 2px solid #cfbd97;
}

button{
    background-color:#cfbd97;
    color:#000;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #e0ce9b;
}


.link{
    color:#fff;
    text-decoration: none;
    text-align: center;
    margin-top: 45px;
}


.link:hover, .link:focus, .link:active {
    color:#e0ce9b;
    text-decoration: underline;
}


@media (max-width: 768px) {
    .form-section {
        width: 75%;
        height: auto;
        padding: 1.5rem;
        top: 45%;
    }

}
</style>