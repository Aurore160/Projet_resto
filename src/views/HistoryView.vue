<template>
  <div class="history-page">
    <HeaderSpecified />
    <main class="container" style="padding:24px">
      <h2>Historique des commandes</h2>
      <div v-if="store.state.invoices.length===0">Aucune facture</div>
      <div v-for="inv in store.state.invoices" :key="inv.id" class="invoice">
        <div class="inv-head">
          <div>
            <strong>Nom:</strong> {{ inv.user?.firstName || inv.user?.email }}<br/>
            <strong>Date:</strong> {{ new Date(inv.date).toLocaleString() }}
          </div>
          <div>
            <strong>Sub total:</strong> ${{ inv.subtotal }}<br/>
            <strong>Tax:</strong> ${{ inv.tax }}<br/>
            <strong>Total:</strong> ${{ inv.total }}
          </div>
        </div>

        <table class="inv-table">
          <thead><tr><th>Plat</th><th>Prix</th><th>Qte</th><th>Montant total</th></tr></thead>
          <tbody>
            <tr v-for="it in inv.items" :key="it.id">
              <td>{{ it.name }}</td>
              <td>{{ it.price }}</td>
              <td>{{ it.qty }}</td>
              <td>${{ (parseFloat(it.price.replace("$","")) * it.qty).toFixed(2) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </main>
    <FooterSpecified />
  </div>
</template>

<script setup>
import HeaderSpecified from "@/component/HeaderSpecified.vue";
import FooterSpecified from "@/component/FooterSpecified.vue";
import {useUserStore }from "@/stores/userStore.js";
const useStore = useUserStore();
</script>

<style scoped>


.invoice{ 
    background: #cfbd97; 
    padding:16px; 
    border-radius:8px; 
    margin-bottom:16px; 
}

.inv-head{ 
    display:flex; 
    justify-content:space-between; 
    margin-bottom:12px; 
}

.inv-table{ 
    width:100%; 
    border-collapse:collapse; 
    background:#fff; 
    border-radius:6px; 
    overflow:hidden; 
}

.inv-table th, .inv-table td{ 
    padding:12px; 
    border-bottom:1px solid #eee; 
    text-align:left; 
}

</style>