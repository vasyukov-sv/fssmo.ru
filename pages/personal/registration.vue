<template>
	<div class="page-content">
		<HeaderContent/>
		<section class="container">
			<form class="page-default-form" method="post" @submit.prevent="send">
				<div class="form-group form-group-label-inline">
					<label class="form-label">Фамилия</label>
					<div class="input-container">
						<input type="text" name="last_name" v-model="last_name" :class="{error: $v.last_name.$error}" @change="$v.last_name.$touch()">
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<label class="form-label">Имя</label>
					<div class="input-container">
						<input type="text" name="name" v-model="name" :class="{error: $v.name.$error}" @change="$v.name.$touch()">
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<label class="form-label">Телефон</label>
					<div class="input-container">
						<input-phone name="phone" v-model="phone" :class="{error: $v.phone.$error}" @change="$v.phone.$touch()"></input-phone>
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<label class="form-label">e-mail</label>
					<div class="input-container">
						<input type="email" name="email" v-model="email" :class="{error: $v.name.$error}" @change="$v.name.$touch()">
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<label class="form-label">пароль</label>
					<div class="input-container">
						<input type="password" name="password" v-model="password" :class="{error: $v.password.$error}" @change="$v.password.$touch()">
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<label class="form-label">повторите пароль</label>
					<div class="input-container">
						<input type="password" name="password_confirm" v-model="passwordConfirm" :class="{error: $v.passwordConfirm.$error}" @change="$v.passwordConfirm.$touch()">
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<div class="checkbox">
						<input type="checkbox" id="reg_agree" v-model="agree" :class="{error: $v.agree.$error}" @change="$v.agree.$touch()">
						<label for="reg_agree">Я согласен с условиями обработки и хранения персональных данных</label>
					</div>
				</div>
				<div class="form-group form-group-label-inline">
					<div class="form-btn">
						<button type="submit" class="btn btn-gold">Зарегистрироваться</button>
					</div>
				</div>
			</form>
		</section>
	</div>
</template>

<script>
	import InputPhone from '~/components/inputPhone';
	import SuccessModal from '~/components/successModal.vue'
	import { required, email } from 'vuelidate/lib/validators'
	import gql from 'graphql-tag'

	const createProfile = gql`mutation ($user: UserCreateInput!) {
		userCreate (user: $user) {
			status
			user {
				id
			}
		}
	}`;

	export default {
		middleware: 'guest',
		components: {
			InputPhone,
		},
		async asyncData (context) {
			await context.store.dispatch('getPageInfo')
		},
		data ()
		{
			return {
				name: '',
				last_name: '',
				email: '',
				phone: '',
				password: '',
				passwordConfirm: '',
				agree: false
			}
		},
		validations: {
			name: {
				required
			},
			last_name: {
				required
			},
			phone: {
				required
			},
			email: {
				required,
				email
			},
			password: {
				required
			},
			passwordConfirm: {
				required
			},
			agree: {
				required (val) {
					return val;
				}
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
					await this.$apollo.mutate({
						mutation: createProfile,
						variables: {
							user: {
								name: this.name,
								last_name: this.last_name,
								email: this.email,
								phone: this.phone,
								password: this.password,
								password_confirm: this.passwordConfirm,
							}
						}
					})
					.then((data) =>
					{
						if (data.errors !== undefined)
						{
							this.$modal.show(SuccessModal, {
								text: data.errors[0].message,
								error: true,
							})
						}
						else
						{
							this.$store.commit('currentUser', data.data['userCreate']['user'])

							this.$nextTick(() => {
								this.$router.push('/personal/')
							})
						}
					})
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			}
		}
	}
</script>