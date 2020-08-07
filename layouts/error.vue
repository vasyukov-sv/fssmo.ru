<template>
	<div class="page-error">
		<div class="container-fluid">
			<div class="page-error-content" v-if="error.statusCode === 404">
				<div class="page-error-title">
					<h1>{{ error.message }}</h1>
					<div class="page-error-text">
						Пожалуйста, проверьте введеный адрес или вернитесь <nuxt-link to="/">на главную</nuxt-link>.
					</div>
				</div>
				<div class="error-numbers">
					4<span class="error-zero"><span class="num-borders"></span></span>4
				</div>
			</div>
			<div v-else class="page-error-content">
				<div class="page-error-title">
					<h1>Произошла ошибка</h1>
					<div class="page-error-text">{{ error.message }}</div>
				</div>
				<div class="error-numbers">
					5<span class="error-zero"><span class="num-borders"></span></span>0
				</div>
			</div>
		</div>
	</div>
</template>

<script>
	export default {
		name: 'error',
		props: {
			error: {
				type: Object
			}
		},
		head () {
			return {
				title: this.error.message
			}
		},
	}
</script>