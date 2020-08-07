<template>
	<div class="auth-popup-container-section">
		<div class="dropdown-popup-content">
			<h4>восстановить пароль</h4>
			<div class="form-note-text" v-if="error.length > 0">{{ error }}</div>
			<form action="" method="post" @submit.prevent="send" class="form-remember-home">
				<div class="dropdown-popup-header-caption">
					Отправим на почту письмо с инструкцией по восстановлению пароля
				</div>
				<div class="form-group">
					<input type="email" name="email" v-model="email" :class="{error: $v.email.$error}" @change="$v.email.$touch()" placeholder="E-mail" />
					<transition name="fade">
						<p v-if="$v.email.$error" class="form-error-text">Введите корректный E-mail</p>
					</transition>
				</div>
				<div class="form-group-btn">
					<button type="submit" class="btn btn-gold">отправить</button>
				</div>
			</form>
		</div>
		<div class="dropdown-popup-footer">
			<a href="#" @click.prevent="$emit('showLoginForm')">Я вспомнил свой пароль</a>
		</div>
	</div>
</template>

<script>
	import gql from 'graphql-tag'
	import { required, email } from 'vuelidate/lib/validators'

	const rememberMutation = gql`mutation ($email: String!) {
		result: userRemember(login: $email)
	}`;

	export default {
		name: 'auth-remember-form',
		data () {
			return {
				error: '',
				email: '',
				success: false
			}
		},
		validations: {
			email: {
				required,
				email
			},
		},
		methods: {
			async send ()
			{
				this.$v.$touch()

				if (this.$v.$invalid)
					return

				try
				{
					const result = await this.$apollo.mutate({
						mutation: rememberMutation,
						variables: {
							email: this.email,
						}
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['result']
					})

					this.success = true
					this.error = result
				}
				catch (e)
				{
					this.success = false
					this.error = e.message
				}
			}
		}
	}
</script>