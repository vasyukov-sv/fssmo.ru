<template>
	<div class="page-content">
		<HeaderContent/>
		<div class="personal-page-container" sticky-container>
			<section class="container">
				<div class="row">
					<div class="col-md-4 order-md-1">
						<div class="personal-menu">
							<div v-sticky>
								<Menu/>
							</div>
						</div>
					</div>
					<div class="col-md-8">
						<form class="personal-data" method="post" @submit.prevent="send">
							<h4 v-if="!item">Добавление нового события в народный календарь</h4>
							<h4 v-else>Редактирование события в народном календаре</h4>
							<div class="page-default-form">
								<div class="form-group form-group-label-inline">
									<label class="form-label">Дата начала&nbsp;*</label>
									<div class="input-container">
										<client-only>
											<datepicker
												v-model="form.date_start"
												placeholder="дд.мм.гггг"
												:monday-first="true"
												:clear-button="true"
												:language="ru"
												format="dd.MM.yyyy"
												name="date_start"
												:class="{error: $v.form.date_start.$error}"
												@selected="$v.form.date_start.$touch()"
											></datepicker>
											<div slot="placeholder">
												<input type="date" name="date_start" placeholder="дд.мм.гггг" v-model="form.date_start" :class="{error: $v.form.date_start.$error}" @selected="$v.form.date_start.$touch()">
											</div>
										</client-only>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Дата окончания&nbsp;*</label>
									<div class="input-container">
										<client-only>
											<datepicker
												v-model="form.date_end"
												placeholder="дд.мм.гггг"
												:monday-first="true"
												:clear-button="true"
												:language="ru"
												format="dd.MM.yyyy"
												name="date_end"
												:class="{error: $v.form.date_end.$error}"
												@selected="$v.form.date_end.$touch()"
											></datepicker>
											<div slot="placeholder">
												<input type="date" name="date_end" placeholder="дд.мм.гггг" v-model="form.date_end" :class="{error: $v.form.date_end.$error}" @selected="$v.form.date_end.$touch()">
											</div>
										</client-only>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Название&nbsp;*</label>
									<div class="input-container">
										<input type="text" name="name" v-model="form.name">
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Дисциплина&nbsp;*</label>
									<div class="input-container">
										<select name="discipline" v-model="form.discipline" :class="{error: $v.form.discipline.$error}" @change="$v.form.discipline.$touch()">
											<option value="">...</option>
											<option v-for="discipline in disciplines" :value="discipline['id']">{{ discipline['title'] }}</option>
										</select>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Количество мишеней&nbsp;*</label>
									<div class="input-container">
										<select v-model="form.targets" :class="{error: $v.form.targets.$error}" @change="$v.form.targets.$touch()">
											<option value="">...</option>
											<option v-for="item in targets" :value="item['id']">{{ item['value'] }}</option>
										</select>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Статус</label>
									<div class="input-container">
										<select v-model="form.status" :class="{error: $v.form.status.$error}" @change="$v.form.status.$touch()">
											<option value="">...</option>
											<option v-for="item in status" :value="item['id']">{{ item['value'] }}</option>
										</select>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Страна&nbsp;*</label>
									<div class="input-container">
										<Multiselect
											v-model="form.country"
											track-by="id"
											label="title"
											placeholder="..."
											:options="country"
											:searchable="true"
											:showLabels="false"
											:class="{error: $v.form.country.$error}"
											@change="$v.form.country.$touch()"
										>
											<template slot="noResult">
												Элементов не найдено
											</template>
										</Multiselect>
									</div>
								</div>
								<div v-if="isRussia" class="form-group form-group-label-inline">
									<label class="form-label">Федеральный округ&nbsp;*</label>
									<div class="input-container">
										<select name="district" v-model="form.district" :class="{error: $v.form.district.$error}" @change="$v.form.district.$touch()">
											<option v-for="item in districs" :value="item['id']">{{ item['value'] }}</option>
										</select>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Город&nbsp;*</label>
									<div class="input-container">
										<input v-if="!isRussia" type="text" name="city" v-model="form.city" :class="{error: $v.form.city.$error}" @change="$v.form.city.$touch()">

										<Multiselect v-else
											v-model="form.city"
											track-by="id"
											label="title"
											placeholder="..."
											:options="city"
											:searchable="true"
											:showLabels="false"
											:class="{error: $v.form.city.$error}"
											@change="$v.form.city.$touch()"
											:loading="citySearching"
											@search-change="citySearch"
											:internal-search="false"
											:show-no-results="false"
											:clear-on-select="false"
										>
											<template slot="noOptions">
												Элементов не найдено
											</template>
										</Multiselect>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Клуб&nbsp;*</label>
									<div class="input-container">
										<select name="club" v-model="form.club" :class="{error: $v.form.club.$error}" @change="$v.form.club.$touch()">
											<option value="">...</option>
											<option v-for="club in clubs" :value="club['id']">{{ club['title'] }}</option>
										</select>
									</div>
								</div>
							</div>
							<div class="page-default-form">
								<h4>Контактная информация</h4>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Сайт</label>
									<div class="input-container">
										<input type="text" name="url" v-model="form.url">
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<div class="form-btn">
										<button type="submit" class="btn btn-gold">{{ item ? 'сохранить' : 'добавить' }} соревнование</button>
									</div>
								</div>
							</div>
						</form>
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

		<FutureEventsBottom/>
		<BottomImg/>
	</div>
</template>

<script>
	import BottomResults from '~/components/resultsBottom.vue'
	import BottomRating from '~/components/ratingBottom.vue'
	import BottomImg from '~/components/bottomImg.vue'
	import FutureEventsBottom from '~/components/futureEventsBottom.vue'
	import Menu from '~/components/personal/menu.vue'
	import Multiselect from 'vue-multiselect'
	import SuccessModal from '~/components/successModal.vue'
	import {en, ru} from 'vuejs-datepicker/dist/locale'

	import { required, minValue, requiredIf } from 'vuelidate/lib/validators'
	import { getClubsQuery, disciplinesQuery, calendarFormQuery, locationsQuery, calendarFormMutation } from '~/utils/query.gql'

	export default {
		name: "calendar-event",
		middleware: 'authorization',
		components: {
			BottomResults,
			BottomImg,
			FutureEventsBottom,
			BottomRating,
			Menu,
			Multiselect
		},
		async asyncData (context)
		{
			try
			{
				let clubsRequest = context.app.$apollo.query({
					query: getClubsQuery,
					fetchPolicy: 'cache-first',
				})

				let disciplinesRequest = context.app.$apollo.query({
					query: disciplinesQuery,
					fetchPolicy: 'cache-first',
				})

				let calendarFormRequest = context.app.$apollo.query({
					query: calendarFormQuery,
					fetchPolicy: 'cache-first',
					variables: {
						id: context.route.params['id'] || 0
					}
				})

				const [
					clubs,
					disciplines,
					country,
					districs,
					targets,
					status,
					item,
				] = await Promise.all([
					clubsRequest,
					disciplinesRequest,
					calendarFormRequest,
					context.store.dispatch('getPageInfo', context.route.path)
				])
				.then(([clubs, disciplines, form]) =>
				{
					if (typeof clubs.errors !== 'undefined')
						throw new Error(clubs.errors[0].message)

					return [
						clubs.data['clubs'],
						disciplines.data['disciplines'],
						form.data['form']['country'],
						form.data['form']['districs'],
						form.data['form']['targets'],
						form.data['form']['status'],
						form.data['form']['item'],
					]
				})

				return {
					clubs: clubs || [],
					disciplines: disciplines || [],
					country: country || [],
					districs: districs || [],
					targets: targets || [],
					status: status || [],
					item: item || null,
				}
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
				form: {
					date_start: '',
					date_end: '',
					name: '',
					club: '',
					discipline: '',
					country: null,
					status: '',
					district: '',
					city: '',
					url: '',
					targets: '',
				},
				city: [],
				citySearching: false,
				en: en,
				ru: ru
			}
		},
		created ()
		{
			if (this.item)
			{
				this.form.date_start = new Date(this.item.active_from)
				this.form.date_end = new Date(this.item.active_to)
				this.form.name = this.item.name
				this.form.discipline = this.item.discipline
				this.form.targets = this.item.targets

				this.citySearching = true

				this.form.country = this.country.find((item) => {
					return item['id'] === this.item.country
				})

				if (this.isRussia)
				{
					this.form.city = {
						title: this.item.city,
						id: this.item.cityId,
					}
				}
				else
					this.form.city = this.item.city

				this.city = [this.form.city]

				this.form.status = this.item.status
				this.form.district = this.item.district
				this.form.url = this.item.site

				this.form.club = this.clubs.find((item) => {
					return item['title'].indexOf(this.item.club) !== -1
				})['id'] || 0

				this.$nextTick(() => {
					this.citySearching = false
				})
			}
		},
		watch: {
			'form.country' ()
			{
				if (this.citySearching === false)
					this.form.city = ''
			}
		},
		computed: {
			isRussia () {
				if (!this.form.country)
					return false

				return this.country.find((item) => {
					return this.form.country['id'] === item['id'] && item['title'].toLowerCase().indexOf('россия') !== -1
				}) !== undefined
			},
		},
		validations: {
			form: {
				date_start: {
					required,
					minValue: value => new Date(value) > new Date()
				},
				date_end: {
					required,
					minValue: function (value) {
						return new Date(value) > new Date() && new Date(value) >= new Date(this.form.date_start)
					}
				},
				name: {
					required
				},
				targets: {
					required,
					minValue: minValue(1),
				},
				discipline: {
					required,
					minValue: minValue(1),
				},
				club: {
					required,
					minValue: minValue(2),
				},
				country: {
					required,
				},
				district: {
					required: requiredIf(function () {
						return this.isRussia
					})
				},
				city: {
					required
				},
				status: {
					required
				},
			}
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
						mutation: calendarFormMutation,
						variables: {
							id: this.$route.params['id'] || 0,
							data: {
								date_start: this.form.date_start,
								date_end: this.form.date_end,
								discipline: this.form.discipline,
								name: this.form.name,
								club: this.form.club,
								country: this.form.country ? this.form.country['id'] : 0,
								district: this.form.district,
								city: typeof this.form.city === 'object' ? this.form.city['id'] : this.form.city,
								status: this.form.status,
								url: this.form.url,
								targets: this.form.targets,
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
						  this.$modal.show(SuccessModal, {
							text: "После проверки модератором информация о соревновании \"" + this.form.name + "\" появится на сайте."
						  })

							this.$nextTick(() => {
								this.$router.push('/calendar/')
							})
						}

						return true
					})
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			},
			async citySearch (query)
			{
				if (query.length <= 1)
					return

				this.citySearching = true

				try
				{
					this.city = await this.$apollo.query({
						query: locationsQuery,
						variables: {
							query: query,
						},
					})
					.then((result) =>
					{
						if (typeof result.errors !== 'undefined')
							throw new Error(result.errors[0].message)

						return result.data['locations']
					})

					this.citySearching = false
				}
				catch (e)
				{
					this.$modal.alert({
						title: 'Ошибка',
						content: e.message
					})
				}
			}
		},
	}
</script>