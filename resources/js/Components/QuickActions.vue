<template>
     <div class="lg:hidden fixed bottom-20 right-4 z-40 flex flex-col items-end gap-2">
          <!-- FAB Button -->
          <TransitionGroup
               enter-active-class="transform transition-all duration-300 ease-out"
               enter-from-class="scale-0 opacity-0 translate-y-4"
               enter-to-class="scale-100 opacity-100 translate-y-0"
               leave-active-class="transform transition-all duration-200 ease-in"
               leave-from-class="scale-100 opacity-100 translate-y-0"
               leave-to-class="scale-0 opacity-0 translate-y-4"
          >
               <button
                    v-for="action in visibleActions"
                    :key="action.id"
                    @click="handleAction(action)"
                    class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all active:scale-95"
                    :class="action.class || ''"
               >
                    <span class="text-lg">{{ action.icon }}</span>
                    <span class="text-sm font-medium">{{ action.label }}</span>
               </button>
          </TransitionGroup>

          <!-- Main FAB -->
          <button
               @click="toggle"
               class="w-14 h-14 rounded-full bg-primary-600 text-white shadow-lg hover:bg-primary-700 hover:shadow-xl transition-all active:scale-95 flex items-center justify-center"
          >
               <svg
                    class="w-6 h-6 transition-transform duration-300"
                    :class="{ 'rotate-45': isOpen }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
               >
                    <path
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         stroke-width="2.5"
                         d="M12 4v16m8-8H4"
                    />
               </svg>
          </button>
     </div>
</template>

<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
     actions: {
          type: Array,
          required: true,
          default: () => [],
     },
     userRole: {
          type: String,
          default: null,
     },
});

const isOpen = ref(false);

const visibleActions = computed(() => {
     return props.actions.filter((action) => {
          if (action.roles && action.roles.length > 0) {
               return action.roles.includes(props.userRole);
          }
          return true;
     });
});

const toggle = () => {
     isOpen.value = !isOpen.value;
};

const handleAction = (action) => {
     isOpen.value = false;
     if (action.handler) {
          action.handler();
     } else if (action.route) {
          router.visit(action.route);
     } else if (action.url) {
          window.location.href = action.url;
     }
};
</script>
