<template>
	<div class="page-content">
		<HeaderContent></HeaderContent>
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
						<div class="chart-year-filter">
							<div class="form-group form-group-label-inline">
								<label class="form-label">Год</label>
								<div class="input-container">
									<select v-model.number="year.chart">
										<option :value="0">Все года</option>
										<option v-for="year in years" :value="year">{{ year }}</option>
									</select>
								</div>
							</div>
							<div class="form-group form-group-label-inline">
								<label class="form-label">Дисциплина</label>
								<div class="input-container">
									<select v-model="discipline.chart">
										<option :value="''">Любая</option>
										<option v-for="it in disciplines" :value="it">{{ it }}</option>
									</select>
								</div>
							</div>
						</div>
						<div class="statistic-charts-container" id="chart_container">
							<canvas class="statistic-charts" ref="chart" width="760" height="320"></canvas>
						</div>

						<div class="personal-stat-table-container">
							<div class="chart-year-filter">
								<div class="form-group form-group-label-inline">
									<label class="form-label">Год</label>
									<div class="input-container">
										<select v-model.number="year.list">
											<option :value="0">Все года</option>
											<option v-for="year in years" :value="year">{{ year }}</option>
										</select>
									</div>
								</div>
								<div class="form-group form-group-label-inline">
									<label class="form-label">Дисциплина</label>
									<div class="input-container">
										<select v-model="discipline.list">
											<option :value="''">Любая</option>
											<option v-for="it in disciplines" :value="it">{{ it }}</option>
										</select>
									</div>
								</div>
							</div>
							<div class="personal-stat-table">
								<table>
									<thead>
										<tr>
											<th rowspan="2">Дата</th>
											<th rowspan="2">Соревнование</th>
											<th colspan="8">Площадки</th>
											<th rowspan="2">Сумма</th>
										</tr>
										<tr class="stands-row">
											<th v-for="i in 8">{{ i }}</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="result in list">
											<td><div class="table-date">{{ result['date'] | date('DD.MM.YYYY') }}</div></td>
											<td><div class="table-competition-title">{{ result['competition'] }}</div></td>
											<td v-for="i in 8">
												{{ result['stands'][i] }}
											</td>
											<td>{{ result['summ'] }}</td>
										</tr>
									</tbody>
								</table>
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
	import Chart from 'chart.js'
	import gql from 'graphql-tag';
	import moment from 'moment';
	import { morph } from '~/plugins/helpers';

	const userResultsQuery = gql`query {
		items: currentUserResults {
			competition
			discipline
			date
			stands
			summ
			or
		}
	}`;

	export default {
		name: 'personal-stats',
		components: {
			BottomResults,
			BottomRating,
			BottomImg,
			FutureEventsBottom,
			Menu,
		},
		async asyncData (context)
		{
			try
			{
				let userRequltsRequest = context.app.$apollo.query({
					query: userResultsQuery
				})

				const [results] = await Promise.all([
					userRequltsRequest,
					context.store.dispatch('getPageInfo')
				])
				.then(([result]) =>
				{
					if (typeof result.errors !== 'undefined')
						throw new Error(result.errors[0].message)

					return [result.data['items']]
				})

				return {
					results: results,
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
		data ()
		{
			return {
				_chart: null,
				year: {
					list: 0,
					chart: moment().year(),
				},
				discipline: {
					list: '',
					chart: '',
				},
				disciplines: [],
			}
		},
		computed: {
			years ()
			{
				let result = []

				this.results.forEach((item) =>
				{
					let y = moment(item['date']).year()

					if (result.indexOf(y) === -1)
						result.push(y)
				})

				return result.sort((a, b) => {
					return a > b ? -1 : 1
				})
			},
			list ()
			{
				return this.results.filter((item) => {
					return this.year.list === 0 || moment(item['date']).year() === this.year.list
				})
				.filter((item) => {
					return this.discipline.list.length === 0 || item['discipline'] === this.discipline.list
				})
			}
		},
		watch: {
			'year.chart' () {
				this.initChart()
			},
			'discipline.chart' () {
				this.initChart()
			}
		},
		created ()
		{
			this.disciplines = this.results
				.map((item) => item['discipline'])
				.filter((x, i, a) => a.indexOf(x) === i)
				.sort((a, b) => a.localeCompare(b))
		},
		mounted () {
			this.initChart()
		},
		methods: {
			initChart ()
			{
				let items = this.results.filter((item) => {
					return this.year.chart === 0 || moment(item['date']).year() === this.year.chart && item['or'] > 0
				})

				if (items.length === 0 && this.year.chart > 0 && this.results.length > 0)
				{
					this.year.chart = moment(this.results[0]['date']).year()
					return
				}

				items = items.filter((item) => {
					return this.discipline.chart.length === 0 || item['discipline'] === this.discipline.chart
				})

				let labels = items.map((item) => {
					return item['or'].toFixed(2).replace(/\./,',')
				})

				let points = items.map((item) => {
					return item['or'].toFixed(2)
				})

				if (this._chart)
					this._chart.destroy()

				this._chart = new Chart(this.$refs['chart'], {
					type: 'bar',
					data: {
						labels: labels,
						datasets: [{
							data: points,
							backgroundColor: '#fa965c',
							barThickness: 10,
						}]
					},
					options: {
						legend: {
							display: false,
						},
						scales: {
							yAxes: [{
								ticks: {
									beginAtZero: true,
									stepSize: 50,
									min: 60,
									max: 100,
									padding: 15,
									fontColor: '#868da0',
									fontSize: 14,
									fontFamily: 'Lato'
								},
								gridLines: {
									drawBorder: false,
									zeroLineWidth: 0,
									tickMarkLength: 0
								}
							}],
							xAxes: [{
								ticks: {
									fontColor: '#f47832',
									fontSize: 14,
									fontFamily: 'Lato',
									fontStyle: 'bold'
								},
								gridLines: {
									display: false,
								}
							}]
						},
						tooltips: {
							enabled: false,
							custom (tooltipModel)
							{
								let tooltipEl = document.getElementById('chartjs-tooltip');

								if (!tooltipEl)
								{
									tooltipEl = document.createElement('div');
									tooltipEl.id = 'chartjs-tooltip';

									document.body.appendChild(tooltipEl);
								}

								if (tooltipModel.opacity === 0)
								{
									tooltipEl.style.opacity = 0;
									return;
								}

								if (typeof tooltipModel.dataPoints[0] === 'undefined')
									return;

								let result = items[tooltipModel.dataPoints[0].index];

								let innerHtml = '<div class="tooltip-head">'+tooltipModel.dataPoints[0].label+'</div>';
								innerHtml += '<div class="tooltip-body">' +
									'<div class="tooltip-date">'+moment(result['date']).format('DD.MM.YYYY')+'</div>' +
									'<div class="tooltip-competition">'+result['competition']+'</div>' +
									'<div class="tooltip-targets">'+result['summ']+' '+morph(result['summ'], ['мишень', 'мишени', 'мишеней'])+'</div>' +
									'</div>';

								tooltipEl.innerHTML = innerHtml;

								let position = this._chart.canvas.getBoundingClientRect();

								tooltipEl.style.opacity = 1;
								tooltipEl.style.position = 'absolute';
								tooltipEl.style.left = position.left + window.pageXOffset + tooltipModel.caretX + 'px';
								tooltipEl.style.top = position.top + window.pageYOffset + tooltipModel.caretY + 'px';
								tooltipEl.style.pointerEvents = 'none';
							}
						}
					}
				})
			}
		},
	}
</script>