<template>
    <header class="site-header">
        <div class="header-inner">
            <router-link to="/home" class="logo">
                <img src="@/assets/ZEDUC.png" alt="Logo" />
            </router-link>

            <nav class="main-nav">
                <router-link to="/home" class="nav-item"> Acceuil </router-link>
                <router-link to="/contact" class="nav-item"> Contact </router-link>
                <router-link to="/register" class="nav-item"> S'inscrire </router-link>
                <router-link to="/" class="nav-item btn-login"> Se connecter </router-link>
            </nav>
        </div>
    </header>
</template>

<style scoped>
.site-header {
    width: 100;
    background-color: #cfbd97;
    position: sticky;
    top: 0;
    z-index: 40;
    backdrop-filter: blur(4px);
}

.header-inner{
    max-width: 1200px;
    margin: 0 auto;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo img{
    height: 48px;
    display: block;
    border-radius: 50%;
    margin-right: 90px;
}

.main-nav {
    display: flex;
    gap: 22px;
    align-items:center;
}

.nav-item {
    color: #222;
    text-decoration: none;
    font-weight: 600;
    padding: 6px 10px;
}

.btn-login {
    background: transparent;
    border: 1px solid rgba(0, 0, 0, 0.08);
    padding: 6px 12px;
    border-radius: 8px;
}

@media (max-width: 820px) {
    .main-nav { gap:12px; font-size:14px;}
    .logo img { height: 40px;}
}
</style>