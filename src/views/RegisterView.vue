<template>
    <div class="body">
        <div class="form-section">
            <h3> Créez votre compte </h3>

            <form @submit.prevent="registerUser">  
                <label for="nom"> Nom </label>
                <input type="text" id="nom"  v-model="form.nom" placeholder=" Entrez votre nom " required>
                       
                <label for="prenom">Prénom </label> 
                <input type="text" id="prenom" v-model="form.prenom" placeholder=" Entrez votre prénom " required>
                      
                <label for="email"> Adresse mail </label>
                <input type="email" id="email" v-model="form.mail" placeholder="Entrez votre adresse mail " required>

                <label for="password"> Mot de passe </label>
                <input type="password" id="password" v-model="form.password" placeholder=" Entrez votre mot de passe " required>

                <label  for="confirmPassword"> Confirmer le mot de passe </label>
                <input type="password" id="confirmPassword" v-model="form.passwordConfirmation" placeholder=" Confirmez votre mot de passe " required>

                <label  for="codeParrainage"> Code de votre parrain </label>
                <input type="text" id="codeParrainage" v-model="form.parrainage" placeholder=" Code d'un utilisateur qui vous a parrainé " >


                <button type="submit" class="btn accent"> S'inscrire </button>

                 <p class="login-link">
                    Vous avez déjà un compte ?  
                     <router-link to="/" class="link"> Se connecter </router-link>
                </p>
            </form>
               
        </div>
    </div>
</template>


<script setup> 
import { ref } from "vue";
import { useUserStore } from "@/stores/userStore"

const form = ref({
    prenom: "",
    mail: "",
    password: "", 
    passwordConfirmation: "",
    parrainnage: ""
});


const userStore = useUserStore();

async function registerUser() {
    try {
        if (form.value.password !== form.value.passwordConfirmation) {
            alert("Les mots de passe ne correspondent pas.")
            return
        }
        // back-end pour la simulation d'une inscription 

        const newUser = { ...form.value, ide: Date.now()}
        
        // si on entre le code de parrainage 

        if (form.value.parrainage) {
            const parrain = users.find(u => u.parrainage === form.value.parrainage)
            if (parrain) {
                store.addRefferal(parrain, newUser)
            }
        }

        alert("Inscription réussie !")
        form.value = {nom: "", prenom: "", mail:"", password:"", passwordConfirmation: "", parrainage: ""}
    } catch (err) {
        console.error(err)
    }
}

</script>

<style scoped> 

.body {
    background-image: url("@/assets/register.jpg");
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
    width: 48%;
    height: 90%;
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



h3 {
    color: #fff;
    margin-bottom: 1.5rem;
    font-size: 1.9rem;
    text-align: center;
    
}

form {
    display: flex;
    flex-direction: column;
    text-align: left;
    margin: 0 auto;
    width: 60%;
}

label {
    color: #fff;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

input {
    background-color: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding-left: 0.3rem;
    padding-left: 0.3rem;
    border-radius: 8px;
    color: #fff;
    margin-bottom: 0.7rem;
    font-size: 1rem;
}

.form-section, input {
    padding-left: 0.3rem;
    padding-right: 0.3rem;
}

input:focus {
    outline: 2px solid #cfbd97;
}

button {
    background-color: #cfbd97;
    color: #000;
    font-weight: bold;
    border: none;
    padding: 0.8rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #e0ce9b;
}

.message {
    text-align: center;
    color: #cfbd97;
    margin-top: 1rem;
}

.login-link {
    color: #fff;
    text-align: center;
    margin-bottom: 1.8rem;
    font-size: 1rem;
}

.link {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    margin-left: 5px;
    transition: color O.3s;
}

.link:hover, .link:focus, .link:active {
    color:#cfbd97;
    text-decoration: underline;
}

@media(max-width: 768px) {
    .form-section {
        width:80%;
        height:85%;
        padding: 1.5rem;
    }
    .login-link {
        font-size: 0.8rem;
        
    }
    form{
        width: 80%;
    }
    h3{
        font-size:1.3rem;
    }
}
</style>