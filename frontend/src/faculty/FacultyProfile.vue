<script setup>
import { computed, onMounted, ref } from 'vue'

import api from '../api/axios'
import FacultySideBar from './components/FacultySideBar.vue'
import '../assets/faculty/FacultyProfile.css'

const profile = ref(null)
const isLoading = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
let messageTimer

const form = ref({
	username: '', email: '', password: '', section_id: '',
	firstname: '', middlename: '', lastname: '', name_ext: '', gender: '', address: '',
})

const fullName = computed(() => [profile.value?.firstname, profile.value?.middlename, profile.value?.lastname, profile.value?.name_ext].filter(Boolean).join(' ') || profile.value?.username || 'Faculty member')
const sectionLabel = computed(() => [profile.value?.section_name, profile.value?.program_name, profile.value?.college_name].filter(Boolean).join(' - ') || 'Assigned section')

function showMessage(type, message) {
	errorMessage.value = type === 'error' ? message : ''
	successMessage.value = type === 'success' ? message : ''
	clearTimeout(messageTimer)
	messageTimer = setTimeout(() => { errorMessage.value = ''; successMessage.value = '' }, 3500)
}

function apiError(error) { return error.response?.data?.message || error.message || 'Request failed.' }

function applyProfile(data) {
	profile.value = data
	Object.assign(form.value, {
		username: data?.username || '', email: data?.email || '', password: '', section_id: data?.section_id || '',
		firstname: data?.firstname || '', middlename: data?.middlename || '', lastname: data?.lastname || '',
		name_ext: data?.name_ext || '', gender: data?.gender || '', address: data?.address || '',
	})
}

function cancelEdit() {
	applyProfile(profile.value)
	isEditing.value = false
}

async function loadProfile() {
	isLoading.value = true
	try {
		const response = await api.get('/api/faculty/profile')
		applyProfile(response.data?.data)
	} catch (error) { showMessage('error', apiError(error)) } finally { isLoading.value = false }
}

async function saveProfile() {
	isSaving.value = true
	try {
		const response = await api.put('/api/faculty/profile', { ...form.value, section_id: Number(form.value.section_id) })
		applyProfile(response.data?.data)
		isEditing.value = false
		showMessage('success', 'Faculty profile updated successfully.')
	} catch (error) { showMessage('error', apiError(error)) } finally { isSaving.value = false }
}

onMounted(loadProfile)
</script>

<template>
	<div class="faculty-layout">
		<FacultySideBar />
		<main class="faculty-profile-page">
			<header class="page-header"><div><p class="eyebrow">Faculty account</p><h1>My profile</h1><p>View and manage your faculty information.</p></div><button v-if="!isEditing && profile" type="button" @click="isEditing = true">Edit profile</button></header>
			<div class="message-container" aria-live="polite"><p v-if="errorMessage" class="message error" role="alert">{{ errorMessage }}</p><p v-if="successMessage" class="message success" role="status">{{ successMessage }}</p></div>
			<p v-if="isLoading">Loading profile...</p>

			<section v-else-if="profile && !isEditing" class="profile-view-card">
				<div class="profile-identity"><div class="profile-avatar">{{ fullName.charAt(0).toUpperCase() }}</div><div><h2>{{ fullName }}</h2><p>{{ profile.email }}</p><span class="profile-role">Faculty coordinator</span></div></div>
				<div class="profile-details"><div><span>Username</span><strong>{{ profile.username }}</strong></div><div><span>Gender</span><strong>{{ profile.gender }}</strong></div><div><span>Assigned section</span><strong>{{ sectionLabel }}</strong></div><div class="profile-detail-wide"><span>Address</span><strong>{{ profile.address }}</strong></div></div>
			</section>

			<form v-else class="profile-edit-card" @submit.prevent="saveProfile">
				<div class="profile-card-heading"><div><h2>Edit faculty profile</h2><p>Your assigned section is managed by an administrator.</p></div></div>
				<div class="profile-form-grid"><label>Username<input v-model.trim="form.username" required maxlength="100" /></label><label>Email<input v-model.trim="form.email" type="email" required maxlength="100" /></label><label>Password<input v-model="form.password" type="password" minlength="1" autocomplete="new-password" /><small>Leave blank to keep your current password.</small></label><label>Assigned section<input :value="sectionLabel" disabled /></label><label>First name<input v-model.trim="form.firstname" required maxlength="50" /></label><label>Middle name<input v-model.trim="form.middlename" maxlength="50" /></label><label>Last name<input v-model.trim="form.lastname" required maxlength="50" /></label><label>Name extension<input v-model.trim="form.name_ext" maxlength="5" /></label><label>Gender<select v-model="form.gender" required><option disabled value="">Select gender</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></label><label class="profile-detail-wide">Address<textarea v-model.trim="form.address" required maxlength="255" rows="4"></textarea></label></div>
				<div class="profile-actions"><button type="button" :disabled="isSaving" @click="cancelEdit">Cancel</button><button type="submit" :disabled="isSaving">{{ isSaving ? 'Saving...' : 'Save changes' }}</button></div>
			</form>
		</main>
	</div>
</template>
