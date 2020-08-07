<template>
	<div class="sidebar-menu-wrap">
		<ul class="sidebar-menu">
			<li><nuxt-link to="/personal/competitions/">Мои будущие соревнования</nuxt-link></li>
			<li><nuxt-link to="/personal/rating/">Мое место в рейтинге</nuxt-link></li>
			<li><nuxt-link to="/personal/stats/">моя статистика</nuxt-link></li>
			<li><nuxt-link to="/personal/">личные данные</nuxt-link></li>
			<li><nuxt-link to="/personal/calendar-events/">Мои соревнования в народном календаре</nuxt-link></li>
		</ul>
		<nuxt-link to="/personal/calendar/" class="btn btn-gold">Сообщить о соревновании</nuxt-link>
	</div>
</template>

<script>
	export default {
		name: "personal-menu"
	}
</script>
