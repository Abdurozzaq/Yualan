<template>
  <div>
    <!-- Error Alert -->
    <div v-if="error" class="alert alert-danger" style="margin-bottom: 1rem;">
      {{ error }}
    </div>
    <!-- ...existing order page content... -->
    <OrderForm
      :products="products"
      :categories="categories"
      :customers="customers"
      :tenantSlug="tenantSlug"
      :tenantName="tenantName"
      :ipaymuConfigured="ipaymuConfigured"
      :midtransConfigured="midtransConfigured"
      :midtransClientKey="midtransClientKey"
      :snapToken="snapToken"
    />
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import OrderForm from './OrderForm.vue'; // Ganti sesuai komponen form order Anda

const page = usePage();
const error = page.props.error || null;
const products = page.props.products;
const categories = page.props.categories;
const customers = page.props.customers;
const tenantSlug = page.props.tenantSlug;
const tenantName = page.props.tenantName;
const ipaymuConfigured = page.props.ipaymuConfigured;
const midtransConfigured = page.props.midtransConfigured;
const midtransClientKey = page.props.midtransClientKey;
const snapToken = page.props.snapToken;
</script>

<style scoped>
.alert-danger {
  background: #f8d7da;
  color: #842029;
  border: 1px solid #f5c2c7;
  padding: 1rem;
  border-radius: 4px;
}
</style>
