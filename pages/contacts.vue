<template>
	<div class="page-content">
		<HeaderContent class="page-content-header-dark"/>
		<div class="page-content-dark-container">
			<section class="container">
				<div class="contacts-top">
					<div class="row">
						<div class="col-lg-8" v-editor:block="[{code: 'CONTACTS_MAP', name: 'Карта', type: 'MAP'}]">
							<client-only>
								<GmapMap
									:center="position"
									:zoom="16"
									:options="{disableDefaultUI: true, styles: map.style}"
								>
									<GmapMarker
										:position="position"
										:icon = "{url: '/images/map-marker.svg', size: {width: 49, height: 78, f: 'px', b: 'px'}, anchor: {x: 25, y: 78}}"
									></GmapMarker>
								</GmapMap>
							</client-only>
						</div>
						<div class="col-lg-4">
							<div class="contacts-info">
								<div class="contacts-info-text" v-editor:block="[{code: 'CONTACTS_ADDRESS', name: 'Адрес', type: 'TEXT'}, {code: 'CONTACTS_PHONE', name: 'Телефон', type: 'TEXT'}, {code: 'CONTACTS_EMAIL', name: 'Email', type: 'TEXT'}]">
									<div class="contacts-info-item">
										<span class="event-icon event-icon-map"></span>{{ $store.state.area['CONTACTS_ADDRESS'] }}
									</div>
									<a :href="'tel:'+$store.state.area['CONTACTS_PHONE'].replace(/([- ()])/ig, '')" class="contacts-info-item">
										<span class="event-icon event-icon-phone"></span>{{ $store.state.area['CONTACTS_PHONE'] }}
									</a>
									<a :href="'mailto:'+$store.state.area['CONTACTS_EMAIL']" class="contacts-info-item">
										<span class="event-icon event-icon-mail"></span>{{ $store.state.area['CONTACTS_EMAIL'] }}
									</a>
								</div>
								<hr />
								<ContactsForm/>
							</div>
						</div>
					</div>
				</div>

			</section>

		</div>

		<section class="blue-img-section">
			<div class="container">
				<BottomResults/>
				<BottomRating/>
			</div>
		</section>

	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import ContactsForm from '~/components/contacts/form.vue'

	export default {
		name: 'contacts',
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
			ContactsForm
		},
		async asyncData (context)
		{
			try
			{
				await Promise.all([
					context.store.dispatch('getFutureEvents'),
					context.store.dispatch('getRatingsTypes'),
					context.store.dispatch('getLastResults'),
					context.store.dispatch('getPageInfo')
				])

				return {}
			}
			catch (e)
			{
				return context.error({
					statusCode: 500,
					message: e.message,
				})
			}
		},
		data () {
			return {
				map: {
					style: [
						{
							"featureType": "all",
							"elementType": "labels.text.fill",
							"stylers": [
								{
									"color": "#101010"
								}
							]
						},
						{
							"featureType": "all",
							"elementType": "labels.text.stroke",
							"stylers": [
								{
									"visibility": "on"
								},
								{
									"color": "#f4f2f2"
								},
								{
									"weight": "3.03"
								}
							]
						},
						{
							"featureType": "landscape",
							"elementType": "geometry",
							"stylers": [
								{
									"hue": "#002fff"
								},
								{
									"saturation": "-100"
								},
								{
									"lightness": "0"
								},
								{
									"gamma": "1"
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#eeecec"
								}
							]
						},
						{
							"featureType": "poi",
							"elementType": "labels.icon",
							"stylers": [
								{
									"color": "#fa965c"
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#f5a678"
								}
							]
						},
						{
							"featureType": "road",
							"elementType": "labels.icon",
							"stylers": [
								{
									"hue": "#0011ff"
								},
								{
									"saturation": "-43"
								},
								{
									"lightness": "0"
								},
								{
									"gamma": "0.52"
								}
							]
						},
						{
							"featureType": "transit",
							"elementType": "geometry",
							"stylers": [
								{
									"hue": "#0011ff"
								},
								{
									"lightness": "26"
								}
							]
						},
						{
							"featureType": "transit",
							"elementType": "labels.icon",
							"stylers": [
								{
									"hue": "#0011ff"
								},
								{
									"saturation": "-43"
								},
								{
									"gamma": "0.52"
								}
							]
						},
						{
							"featureType": "water",
							"elementType": "geometry",
							"stylers": [
								{
									"color": "#102661"
								}
							]
						}
					]
				}
			}
		},
		computed: {
			position ()
			{
				let s = this.$store.state.area['CONTACTS_MAP'].split(',')

				return {lat: parseFloat(s[0]), lng: parseFloat(s[1])};
			}
		}
	}
</script>