<template>
  <div v-if="visible" class="dialog-overlay" @click="handleCancel">
    <div class="dialog" @click.stop>
      <div class="dialog-header">
        <h3>确认删除</h3>
      </div>
      <div class="dialog-content">
        <p>是否删除 {{ title }}？</p>
      </div>
      <div class="dialog-footer">
        <button @click="handleCancel" class="cancel-btn">取消</button>
        <button @click="handleConfirm" class="confirm-btn">确认</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['close', 'confirm'])

const handleCancel = () => {
  emit('close')
}

const handleConfirm = () => {
  emit('confirm')
}
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.dialog {
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 300px;
  max-width: 90%;
}

.dialog-header {
  padding: 16px 24px 8px;
  border-bottom: 1px solid #eee;
}

.dialog-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.dialog-content {
  padding: 20px 24px;
}

.dialog-content p {
  margin: 0;
  font-size: 16px;
  line-height: 1.5;
}

.dialog-footer {
  padding: 16px 24px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  border-top: 1px solid #eee;
}

.dialog-footer button {
  padding: 8px 16px;
  border-radius: 4px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
}

.dialog-footer button:hover {
  background: #f5f5f5;
}

.confirm-btn {
  background: #dc3545 !important;
  color: white !important;
  border-color: #dc3545 !important;
}

.confirm-btn:hover {
  background: #c82333 !important;
}

.cancel-btn {
  background: #f8f9fa !important;
  color: #212529 !important;
  border-color: #dee2e6 !important;
}

.cancel-btn:hover {
  background: #e2e6ea !important;
}
</style>