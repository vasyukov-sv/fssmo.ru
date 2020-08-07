<template>
	<div class="event-item">
		<a :href="item.url" @click.prevent="click" class="event-item-container" :title="item.title">
			<div class="event-top">
				<div class="event-top-info">
					<div class="event-date" v-html="date" v-if="date"></div>
					<div class="event-type" v-html="item.discipline" v-if="item.discipline"></div>
				</div>
				<div class="event-title" v-html="item.title" v-if="item.title"></div>
				<div class="event-location" v-if="item.location">
					<span class="icon-picker"></span> {{ item.location }}
				</div>
			</div>
		</a>
		<div class="event-img-container">
			<a v-if="item.image" :href="item.url" @click.prevent="click" class="event-img" :style="{backgroundImage: 'url('+item.image+')'}"></a>
			<a v-else :href="item.url" class="no-img-holder"></a>
			<div class="event-item-btns">
				<template v-if="this.isExternal">
					<a :href="item.url" target="_blank" class="btn btn-white-border">подробнее</a>
				</template>
				<template v-else>
					<nuxt-link :to="item.url" class="btn btn-white-border">подробнее</nuxt-link>
					<slot name="buttons">
						<nuxt-link v-if="item.registration" :to="item.url+'registration/'" class="btn btn-gold" :class="{disabled: !$store.getters.isAuthorized}">Зарегистрироваться</nuxt-link>
					</slot>
				</template>
			</div>
		</div>
	</div>
</template>

<script>
	import moment from 'moment';

	export default {
		name: "eventItem",
		props: {
			item: {
				type: Object
			},
		},
		computed: {
			date ()
			{
				let from = moment(this.item['date_from']);
				let to = moment(this.item['date_to']);

				if (from.isSame(to))
					return moment(from).format('DD MMMM');
				else
					return moment(from).format('DD')+'-'+moment(to).format('DD MMMM');
			},
			isExternal () {
				return this.item['url'].indexOf('http') >= 0;
			}
		},
		methods: {
			click ()
			{
				if (!this.isExternal)
					this.$router.push(this.item.url);
				else
					window.open(this.item.url);
			}
		}
	}
</script>