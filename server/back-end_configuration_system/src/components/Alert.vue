<template>
  <div v-if="visible" class="alert-overlay" @click="closeOnOverlay">
    <div class="alert-box" :class="type">
      <div class="alert-header">
        <h3>{{ title }}</h3>
        <button class="alert-close" @click="close">&times;</button>
      </div>
      <div class="alert-content">
        <p>{{ message }}</p>
      </div>
      <div class="alert-footer">
        <button 
          class="alert-button" 
          :class="type"
          @click="confirm"
        >
          确定
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: '提示'
  },
  message: {
    type: String,
    required: true
  },
  type: {
    type: String,
    default: 'info', // info, success, warning, error
    validator: (value) => ['info', 'success', 'warning', 'error'].includes(value)
  },
  closeOnOverlayClick: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const visible = ref(props.modelValue)

watch(() => props.modelValue, (newVal) => {
  visible.value = newVal
})

const close = () => {
  visible.value = false
  emit('update:modelValue', false)
}

const confirm = () => {
  close()
  emit('confirm')
}

const closeOnOverlay = (e) => {
  if (props.closeOnOverlayClick && e.target === e.currentTarget) {
    close()
  }
}
</script>

<style scoped>
.alert-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.alert-box {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  width: 90%;
  max-width: 400px;
  overflow: hidden;
}

.alert-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #eee;
}

.alert-header h3 {
  margin: 0;
  font-size: 1.2rem;
}

.alert-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #999;
}

.alert-close:hover {
  color: #333;
}

.alert-content {
  padding: 1.5rem;
}

.alert-content p {
  margin: 0;
  font-size: 1rem;
  line-height: 1.5;
}

.alert-footer {
  padding: 1rem 1.5rem;
  text-align: right;
  border-top: 1px solid #eee;
}

.alert-button {
  padding: 0.5rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

.alert-button.info {
  background-color: #17a2b8;
  color: white;
}

.alert-button.success {
  background-color: #28a745;
  color: white;
}

.alert-button.warning {
  background-color: #ffc107;
  color: #212529;
}

.alert-button.error {
  background-color: #dc3545;
  color: white;
}

.alert-button:hover {
  opacity: 0.9;
}
</style>