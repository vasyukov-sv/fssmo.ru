<template>
	<section v-if="disciplines.length" class="page-section section-gray">
		<div class="container">
			<div class="future-events">
				<h2>Будущие соревнования</h2>
				<nav class="page-list-nav">
					<ul>
						<li>
							<a href="#" @click.prevent="setActive(0)" :class="{ active: isActive(0) }">Все</a>
						</li>
						<li v-for="discipline in disciplines">
							<a href="#" @click.prevent="setActive(discipline['id'])" :class="{ active: isActive(discipline['id']) }">{{ discipline['title'] }}</a>
						</li>
					</ul>
				</nav>
				<div class="events-list">
					<div class="row row-eq-height">
						<div v-for="item in competitions" :key="item.id" class="event-col">
							<EventItem :item="item"></EventItem>
						</div>
					</div>
				</div>
				<div class="loadmore-btn">
					<nuxt-link to="/competitions/" class="btn btn-gold">все соревнования</nuxt-link>
				</div>
			</div>
		</div>
	</section>
</template>

<script>
	import EventItem from '~/components/competitions/eventItem.vue';

	export default {
		components: {
			EventItem
		},
		data () {
			return {
				activeDiscipline: 0
			}
		},
		computed: {
			competitions () {
				return this.$store.state.futureEvents.competitions || []
			},
			disciplines () {
				return this.$store.state.futureEvents.disciplines || []
			}
		},
		methods: {
			isActive (id) {
				return this.activeDiscipline === id;
			},
			setActive (id) {
				this.activeDiscipline = id;
				this.updateEventsList(id);
			},
			updateEventsList (disciplineId) {
				this.$store.dispatch('getFutureEventsByDisciplineId', disciplineId);
			},
		}
	}
</script>