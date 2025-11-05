<template>
    <div class="body">
        <div class="form-section">
            <h2> Réinitialisation du mot de passe </h2>
            <p> Entrez le lien reçu et votre nouveau mot de passe </p>

            <form @submit.prevent="recoverAccount">  
                <label for="link"> Copier le lien reçu </label>
                <input type="password" id="link"  v-model="link" placeholder=" Collez votre lien de récupération " required>

                <label for="password"> Nouveau mot de passe </label>
                <input type="password" id="password" v-model="password" placeholder=" Entrez votre nouveau mot de passe " required>

                <label  for="confirmPassword"> Confirmez le mot de passe </label>
                <input type="password" id="confirmPassword" v-model="confirmPassword" placeholder=" Confirmez votre mot de passe " required>

                <button type="submit"> Réinitialiser le mot de passe </button>
            </form>

            <router-link to="/" class="link"> Retour à la connexion </router-link>
        </div>
    </div>
</template>


<script setup> 
import { ref } from "vue";

const link = ref("");
const password = ref("");
const confirmPassword = ref("");
 

const recoverAccount =  () => {
    if (password.value !== confirmPassword.value) {
        alert("Les mots de passe ne correspondent pas ! ");
        return;
    }

    if (!link.value) {
        alert("Veuillez entrer le lien de récupération reçu par mail");
    }
    

console.log("Lien : ", link.value);
console.log("Nouveau mot de passe: ", password.value);
alert("Mot de passe réinitialisé avec succès !");



}

</script>

<style scoped> 

.body {
    background-image: url("@/assets/recovery.jpg");
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