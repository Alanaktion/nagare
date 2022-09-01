 <template>
    <AppLayout title="Dashboard">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-neutral-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <Welcome />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Jetstream/Welcome.vue';
</script>
