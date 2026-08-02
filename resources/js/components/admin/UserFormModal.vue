<script setup>
import { ref, watch, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AppModal from '@/components/ui/AppModal.vue'
import api from '@/plugins/axios'

const { t } = useI18n()

const props = defineProps({
    show: { type: Boolean, default: false },
    user: { type: Object, default: null },
})

const emit = defineEmits(['close', 'saved'])

const isEditing = computed(() => !!props.user)
const title = computed(() => isEditing.value ? t('users.edit') : t('users.create'))
const isLoading = ref(false)
const errors = ref({})
const serverError = ref('')
const imagePreview = ref(null)
const showPassword = ref(false)

const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'client',
    profile_image: null,
})

watch(() => props.show, (val) => {
    if (val) {
        errors.value = {}
        serverError.value = ''
        showPassword.value = false
        if (props.user) {
            form.value = {
                name: props.user.name,
                email: props.user.email,
                password: '',
                password_confirmation: '',
                role: props.user.role,
                profile_image: null,
            }
            imagePreview.value = props.user.profile_image_url
        } else {
            form.value = {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                role: 'client',
                profile_image: null,
            }
            imagePreview.value = null
        }
    }
})

function handleImageChange(e) {
    const file = e.target.files[0]
    if (!file) return
    form.value.profile_image = file
    imagePreview.value = URL.createObjectURL(file)
}

async function handleSubmit() {
    errors.value = {}
    serverError.value = ''
    isLoading.value = true

    try {
        const formData = new FormData()
        formData.append('name', form.value.name)
        formData.append('email', form.value.email)
        formData.append('role', form.value.role)

        if (form.value.password) {
            formData.append('password', form.value.password)
            formData.append('password_confirmation', form.value.password_confirmation)
        }

        if (form.value.profile_image) {
            formData.append('profile_image', form.value.profile_image)
        }

        let savedUser

        if (isEditing.value) {
            formData.append('_method', 'PUT')
            const { data } = await api.post(`/admin/users/${props.user.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            savedUser = data.data
        } else {
            const { data } = await api.post('/admin/users', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            savedUser = data.data
        }

        console.log('usuario guardado:', savedUser)
        emit('saved', savedUser)
        emit('close')
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors || {}
        } else {
            serverError.value = t('users.form.error')
        }
    } finally {
        isLoading.value = false
    }
}
</script>

<template>
    <AppModal :show="show" :title="title" size="md" @close="emit('close')">

        <div v-if="serverError" class="alert-error">{{ serverError }}</div>

        <form @submit.prevent="handleSubmit" novalidate>

            <div class="avatar-upload">
                <div class="avatar-preview">
                    <img v-if="imagePreview" :src="imagePreview" alt="preview" />
                    <span v-else class="avatar-placeholder">
                        <i class="fa-solid fa-camera"></i>
                    </span>
                </div>
                <label class="avatar-btn">
                    <i class="fa-solid fa-upload"></i>
                    {{ t('users.form.upload_photo') }}
                    <input type="file" accept="image/*" @change="handleImageChange" hidden />
                </label>
            </div>

            <div class="form-grid">

                <div class="form-group">
                    <label>{{ t('users.form.full_name') }}</label>
                    <input v-model="form.name" type="text" placeholder="John Doe" :class="{ error: errors.name }" />
                    <span v-if="errors.name" class="field-error">{{ errors.name[0] }}</span>
                </div>

                <div class="form-group">
                    <label>{{ t('users.form.email') }}</label>
                    <input v-model="form.email" type="email" placeholder="john@example.com"
                        :class="{ error: errors.email }" />
                    <span v-if="errors.email" class="field-error">{{ errors.email[0] }}</span>
                </div>

                <div class="form-group full-width">
                    <label>{{ t('users.form.role') }}</label>
                    <select v-model="form.role" :class="{ error: errors.role }">
                        <option value="admin">{{ t('users.roles.admin') }}</option>
                        <option value="case_manager">{{ t('users.roles.case_manager') }}</option>
                        <option value="client">{{ t('users.roles.client') }}</option>
                    </select>
                    <span v-if="errors.role" class="field-error">{{ errors.role[0] }}</span>
                </div>

                <div class="form-group full-width">
                    <label>{{ isEditing ? t('users.form.new_password') : t('users.form.password') }}</label>
                    <div class="input-wrapper">
                        <input v-model="form.password" :type="showPassword ? 'text' : 'password'" placeholder="••••••••"
                            :class="{ error: errors.password }" />
                        <button type="button" class="toggle-password" @click="showPassword = !showPassword">
                            <i :class="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                        </button>
                    </div>
                    <span v-if="errors.password" class="field-error">{{ errors.password[0] }}</span>
                </div>

                <div class="form-group full-width">
                    <label>{{ t('users.form.confirm_password') }}</label>
                    <div class="input-wrapper">
                        <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'"
                            placeholder="••••••••" />
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <button type="button" class="btn-cancel" @click="emit('close')">
                    {{ t('users.form.cancel') }}
                </button>
                <button type="submit" class="btn-submit" :disabled="isLoading">
                    <i v-if="isLoading" class="fa-solid fa-spinner fa-spin"></i>
                    {{ isLoading ? t('users.form.saving') : (isEditing ? t('users.form.update_user') :
                        t('users.form.create_user')) }}
                </button>
            </div>

        </form>
    </AppModal>
</template>

<style scoped>
.alert-error {
    margin-bottom: 16px;
    padding: 12px 14px;
    background: rgba(226, 75, 74, 0.15);
    border: 1px solid rgba(226, 75, 74, 0.4);
    color: #ff8a89;
    border-radius: 10px;
    font-size: 13px;
}

.avatar-upload {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
}

.avatar-preview {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #1e2a3a;
    border: 2px solid #2a3a55;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.avatar-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    color: #4a5a70;
    font-size: 1.4rem;
}

.avatar-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid #2a3a55;
    border-radius: 10px;
    color: #c9d4e8;
    font-size: 13px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Segoe UI', sans-serif;
}

.avatar-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}

.avatar-btn i {
    color: #4a90e2;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 12px;
    font-weight: 500;
    color: #7a8aaa;
    font-family: 'Segoe UI', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group select {
    background: #1e2a3a;
    border: 1px solid #2a3a55;
    border-radius: 10px;
    padding: 10px 14px;
    color: #c9d4e8;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
    font-family: 'Segoe UI', sans-serif;
    width: 100%;
    box-sizing: border-box;
}

.form-group select option {
    background: #1e2a3a;
}

.form-group input:focus,
.form-group select:focus {
    border-color: #4a90e2;
}

.form-group input.error,
.form-group select.error {
    border-color: #e24b4a;
}

.field-error {
    color: #ff8a89;
    font-size: 11px;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper input {
    padding-right: 40px;
}

.toggle-password {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    cursor: pointer;
    color: #4a5a70;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}

.toggle-password:hover {
    color: #c9d4e8;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #1e2a3a;
}

.btn-cancel {
    padding: 10px 20px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid #2a3a55;
    border-radius: 10px;
    color: #7a8aaa;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
    font-family: 'Segoe UI', sans-serif;
}

.btn-cancel:hover {
    background: rgba(255, 255, 255, 0.08);
    color: white;
}

.btn-submit {
    padding: 10px 24px;
    background: linear-gradient(180deg, rgba(45, 212, 191, 0.18), rgba(45, 212, 191, 0.10));
    border: 1px solid rgba(45, 212, 191, 0.35);
    border-radius: 10px;
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    font-family: 'Segoe UI', sans-serif;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-submit:hover:not(:disabled) {
    background: linear-gradient(180deg, rgba(45, 212, 191, 0.28), rgba(45, 212, 191, 0.18));
    transform: translateY(-1px);
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 500px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>