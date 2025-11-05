import { createRouter, createWebHistory } from "vue-router"
import LoginView from "@/views/LoginView.vue"
import RegisterView from "@/views/RegisterView.vue"
import HomeUserView from "@/views/HomeUserView.vue"
import HomeAdminView from "@/views/HomeAdminView.vue"
import HomeEmployeeView from "@/views/HomeEmployeeView.vue"
import HomeManagerView from "@/views/HomeManagerView.vue"
import HomeMainView from "@/views/HomeMainView.vue"
import ForgotPasswordView from "@/views/ForgotPasswordView.vue"
import RecoveryView from "@/views/RecoveryView.vue"
import ContactView from "@/views/ContactView.vue"
import MenuView from "@/views/MenuView.vue"
import AboutView from "@/views/AboutView.vue"
import PromotionView from "@/views/PromotionView.vue"
import QuizView from "@/views/QuizView.vue"
import HistoryView from "@/views/HistoryView.vue"
import FavoritesView from "@/views/FavoritesView.vue"
import OrdersView from "@/views/OrdersView.vue"
import StatisticsView from "@/views/StatisticsView.vue"
import AccountsView from "@/views/AccountsView.vue"
import ReviewsView from "@/views/ReviewsView.vue"


const routes = [
    {path: "/" , name: "login", component:LoginView },
    {path: "/register" , name: "register", component: RegisterView },
    {path: "/forgot" , name: "forgot", component: ForgotPasswordView },
    {path: "/home" , name: "homeMain", component: HomeMainView },
    {path: "/home-user" , name: "homeUser", component: HomeUserView },
    {path: "/home-admin" , name: "homeAdmin", component: HomeAdminView },
    {path: "/home-employee" , name: "homeEmployee", component: HomeEmployeeView},
    {path: "/home-manager", name: "homeManager", component: HomeManagerView},
    {path: "/recovery", name: "recovery", component: RecoveryView},
    {path: "/contact", name:"contact", component: ContactView},
    {path: "/menu", name: "menu", component: MenuView},
    {path: "/about", name: "about", component: AboutView},
    {path: "/promotion", name: "promotion", component: PromotionView},
    {path: "/quiz", name: "quiz", component: QuizView},
    {path: "/history", name: "history", component: HistoryView},
    {path: "/favorites", name: "favorites", component: FavoritesView},
    {path: "/orders", name: "orders", component: OrdersView},
    {path: "/statistics", name: "statistics", component: StatisticsView},
    {path: "/accounts", name: "accounts", component: AccountsView},
    {path: "/reviews", name: "reviews", component: ReviewsView}
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

export default router