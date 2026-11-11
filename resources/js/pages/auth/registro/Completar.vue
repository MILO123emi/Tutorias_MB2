<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

interface Carrera {
    id: number;
    nombre: string;
}

defineProps<{
    correo: string;
    carreras: Carrera[];
}>();

defineOptions({
    layout: {
        title: 'Completá tu perfil',
        description: 'Últimos datos para crear tu cuenta',
    },
});

const form = useForm({
    name: '',
    carrera_id: '',
    telefono: '',
    password: '',
    password_confirmation: '',
});

const enviar = () => {
    form.post('/registro/completar', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Completar registro" />

    <div class="mb-4 text-center text-sm text-muted-foreground">
        {{ correo }}
    </div>

    <form @submit.prevent="enviar" class="flex flex-col gap-6">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Nombre completo</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    placeholder="Nombre y apellido"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="carrera_id">Carrera</Label>
                <Select v-model="form.carrera_id">
                    <SelectTrigger id="carrera_id" class="w-full" :tabindex="2">
                        <SelectValue placeholder="Elegí tu carrera" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="carrera in carreras"
                            :key="carrera.id"
                            :value="String(carrera.id)"
                        >
                            {{ carrera.nombre }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.carrera_id" />
            </div>

            <div class="grid gap-2">
                <Label for="telefono">Teléfono</Label>
                <Input
                    id="telefono"
                    v-model="form.telefono"
                    type="tel"
                    required
                    :tabindex="3"
                    autocomplete="tel"
                    placeholder="9999-9999"
                />
                <InputError :message="form.errors.telefono" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Contraseña</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    placeholder="Contraseña"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirmar contraseña</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    placeholder="Repetí la contraseña"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="6"
                :disabled="form.processing"
            >
                <Spinner v-if="form.processing" />
                Crear cuenta
            </Button>
        </div>
    </form>
</template>
