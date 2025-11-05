import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    token: null,
    favorites: [],
    cart: [],
    notifications: [],
    currencySymbol: localStorage.getItem('currencySymbol') || '$',
    products: JSON.parse(localStorage.getItem('products') || '[]'),
    promotions: JSON.parse(localStorage.getItem('promotions') || '[]'),
    userPoints: JSON.parse(localStorage.getItem('userPoints') || '{}'),
    referrals: JSON.parse(localStorage.getItem('referrals') || '[]'),
    users: JSON.parse(localStorage.getItem('users') || '[]'),
  }),

  actions: {
    login(userData, token) {
      this.user = userData
      this.token = token
      localStorage.setItem('user', JSON.stringify(userData))
      localStorage.setItem('token', token)
    },

    logout() {
      this.user = null
      this.token = null
      localStorage.removeItem('user')
      localStorage.removeItem('token')
    },

    addToCart(item) {
      const existing = this.cart.find(p => p.name === item.name)
      if (existing) existing.qty += item.qty
      else this.cart.push(item)
      localStorage.setItem('cart', JSON.stringify(this.cart))
    },

    removeFromCart(name) {
      this.cart = this.cart.filter(p => p.name !== name)
      localStorage.setItem('cart', JSON.stringify(this.cart))
    },

    toggleFavorite(item) {
      const exists = this.favorites.find(f => f.name === item.name)
      if (exists)
        this.favorites = this.favorites.filter(f => f.name !== item.name)
      else this.favorites.push(item)
      localStorage.setItem('favorites', JSON.stringify(this.favorites))
    },

    
    addProductToStore(newProduct) {
      this.products.unshift(newProduct)
      localStorage.setItem('products', JSON.stringify(this.products))
    },

    addPromotionToStore(newPromo) {
      this.promotions.unshift(newPromo)
      localStorage.setItem('promotions', JSON.stringify(this.promotions))
    },

    loadProductsFromLocal() {
      this.products = JSON.parse(localStorage.getItem('products') || '[]')
    },

    loadPromotionsFromLocal() {
      this.promotions = JSON.parse(localStorage.getItem('promotions') || '[]')
    },

    // --- Parrainage & points ---
    addReferral(userParrain, userParraine) {
      const notif = `🎉 Vous avez parrainé ${userParraine.nom || 'un nouvel utilisateur'} et gagné 100 points !`

      if (!this.userPoints) this.userPoints = {}
      if (!this.userPoints[userParrain.id]) this.userPoints[userParrain.id] = 0
      this.userPoints[userParrain.id] += 100

      if (!this.referrals) this.referrals = []
      this.referrals.push({
        parrainId: userParrain.id,
        parrainNom: userParrain.nom,
        parraineNom: userParraine.nom,
        date: new Date().toISOString()
      })

      this.notifications.push({
        id: Date.now(),
        message: notif,
        type: "success",
        date: new Date().toLocaleString()
      })

      localStorage.setItem("userPoints", JSON.stringify(this.userPoints))
      localStorage.setItem("referrals", JSON.stringify(this.referrals))
      localStorage.setItem("notifications", JSON.stringify(this.notifications))
    },

    loadReferralData() {
      this.userPoints = JSON.parse(localStorage.getItem("userPoints") || "{}")
      this.referrals = JSON.parse(localStorage.getItem("referrals") || "[]")
    },
  }
})


