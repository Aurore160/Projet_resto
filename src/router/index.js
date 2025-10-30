import { createRouter, createWebHistory } from "vue-router"
import LoginView from "@/views/LoginView.vue"
import RegisterView from "@/views/RegisterView.vue"
import HomeUserView from "@/views/HomeUserView.vue"
import HomeAdminView from "@/views/HomeAdminView.vue"
import HomeEmployeeView from "@/views/HomeEmployeeView.vue"
import HomeMainView from "@/views/HomeMainView.vue"
import ForgotPasswordView from "@/views/ForgotPasswordView.vue"

const routes = [
    {path: "/" , name: "login", component:LoginView },
    {path: "/register" , name: "register", component: RegisterView },
    {path: "/forgot" , name: "forgot", component: ForgotPasswordView },
    {path: "/home" , name: "homeMain", component: HomeMainView },
    {path: "/home-user" , name: "homeUser", component: HomeUserView },
    {path: "/home-admin" , name: "homeAdmin", component: HomeAdminView },
    {path: "/home-employee" , name: "homeEmployee", component: HomeEmployeeView}
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router