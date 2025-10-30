<template>
    <div class="home-container animate__animated animate__fadeIn">
        <header>
            <h1> Zeduc-space </h1>
            <nav>
                <router-link to="/"> Se connecter</router-link>
                <router-link to="/register"> S'inscrire</router-link>
            </nav>
        </header>

        <main>
            <h2>Bienvenue sur Zeduc-space </h2>
            <p>
                La plateforme gourmande qui connecte les utilisateurs, employés et administrateurs autour de 
                la gestion de vos commandes de pizza!
            </p>

            <button @click="goLogin"> Se connecter </button>
        </main>
    </div>

</template>

<script setup>
import { useRouter } from "vue-router"
const router = useRouter()
const goLogin = () => router.push("/")
</script>

<style scoped>
.home-container{
    background-color: #000;
    color: #cfbd97;
    height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

header{
    position: absolute;
    top:20px;
    width: 90%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

header h1{
    font-size: 1.5rem;
    color: #cfbd97;
}

nav a {
    margin-left: 20px;
    text-decoration: none;
    color: #cfbd97;
    transition: 0.3s;
}

nav a:hover{
    color:#bda875;
}

button{
    background-color:#cfbd97;
    color:#000;
    border:none;
    border-radius: 5px;
    padding: 12px 25px;
    margin-top: 20px;
    cursor: pointer;
    font-weight: bold;
}

button:hover{
    background-color:#bda875;
}
</style>