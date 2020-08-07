<template>
	<div class="popup-tpl" v-clickaway="close">
		<button class="login-link" :class="{'active': authPopup}" @click="authPopup =! authPopup">
			<span class="icon-login"></span> Войти
		</button>
		<transition name="fade">
			<div class="auth-popup-container" v-if="authPopup">
				<transition name="fade">
					<auth-login-form v-if="authPopupForm" @showRememberForm="showRememberForm"></auth-login-form>
				</transition>
				<transition name="fade">
					<auth-remember-form  v-if="authPopupForget" @showLoginForm="showLoginForm"></auth-remember-form>
				</transition>
			</div>
		</transition>
	</div>
</template>

<script>
	import AuthLoginForm from './authLoginForm.vue'
	import AuthRememberForm from './authRememberForm.vue';
	import { directive as clickaway } from 'vue-clickaway';

	export default {
		directives: {
			clickaway
		},
		components: {
			AuthRememberForm,
			AuthLoginForm
		},
		name: 'auth',
		data () {
			return {
				authPopup: false,
				authPopupForm: true,
				authPopupForget: false,
			}
		},
		methods: {
			close ()
			{
				this.authPopup = false;
				this.authPopupForget = false;
				this.authPopupForm = true;
			},
			showRememberForm ()
			{
				this.authPopupForget = true;
				this.authPopupForm = false;
			},
			showLoginForm ()
			{
				this.authPopupForget = false;
				this.authPopupForm = true;
			}
		}
	}
</script>