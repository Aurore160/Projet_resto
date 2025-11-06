<template>
    <div class="body">
        <div class="form-section">
            <div class="header">
                <router-link  to="/home" class="home-link"> Acceuil </router-link>
            </div>
                
            <div class="title-section">
                <h2 class="welcome"> Bienvenue </h2>
                <h3 class="brand"> chez Zeduc-space </h3>
            </div>

            <form @submit.prevent="login">
                <label for="email"> Adresse mail </label>
                <input type="email" id="email" v-model="email" placeholder="Entrez votre adresse mail" required >

                <label for="password"> Mot de passe </label>
                <input type="password" id="password" v-model="password" placeholder=" Entrez votre mot de passe" required >

                <button type="submit"> Connexion </button>

                <p class="forgot"> 
                    <router-link  to="/forgot" class="link"> Mot de passe oublié ? </router-link> 
                </p>

                <p class="signup">
                    Vous n'avez pas de compte ?
                    <router-link  to="/register" class="link"> S'inscire </router-link>
                </p>
            </form> 

               
            </div>  
    </div>
</template>

<script setup>
import { ref } from "vue"

const email = ref("")
const password = ref("")


const login = async () => {
    console.log("Tentative de connexion avec :" , email.value, password.value);
}


</script>

<style scoped>
.body {
    background-image: url("@/assets/login.jpg");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;

}



@keyframes monAnimationDeFond {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    100%{
        opacity: 1;
        transform: scale(1);
    } 

}

.body{
    background-image: url("@/assets/login.jpg");
    animation: monAnimationDeFond 5s infite; 
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
    height: 77%;
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


.header {
    position: absolute;
    top: 15px;
    right: 25px;
}

form {
    width: 60%;
    margin-left:20px;
    margin-right:20px;
}

.home-link {
    color: #cfbd97;
    text-decoration: none;
    font-weight: bold;
    font-size: 1rem;
    transition: color 0.3s;
}

.home-link:hover, .home-link:focus, .home-link:active {
    color: #cfbd97;
    text-decoration: none;
}

.title-section {
    margin-bottom: 1rem;
}

.welcome {
    color: #fff;
    text-align: center;
    font-size: 2rem;
    margin: 0;
}

.brand {
    color: #cfbd97;
    text-align: center;
    font-size: 1.5rem;
    margin-top: 0.5rem;
}

form {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 80%;
    margin: 0 auto;
    text-align: left;
}

label {
    color: #fff;
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
    align-self: flex-start;
}

input{
    background-color: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 0.7rem;
    border-radius: 8px;
    color: #fff;
    margin-bottom: 1rem;
    width: 100%;
    font-size: 1rem;
}

input:focus {
    outline: 2px solid #cfbd97;
}

button {
    background-color: #cfbd97;
    color: #000;
    font-weight: bold;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
    margin-top: 0.5;
}

button:hover {
    background-color: #e0ce9b;
}

.forgot, .signup {
    text-align: center;
    font-size: 0.9rem;
    margin-top: 1rem;
}

.forgot {
    color: #fff;
}

.signup {
    color: #fff;
}

.link {
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    margin-left: 5px;
    transition: color 0.3s;
}

.link:hover, .link:focus, .link:active {
    color:#cfbd97;
    text-decoration: underline;
} 


@media (max-width: 768px) {
    .form-section {
        width: 80%;
        height: auto;
        padding: 1.5rem;
    }

    .welcome {
        font-size: 1.2rem;

    }

    .brand {
        font-size: 1rem;
    }
}
</style>