<template>
	<div class="shedule-container">
		<table>
			<thead>
				<tr>
					<th rowspan="2" class="time-col">Время</th>
					<th :colspan="places.length">Площадки</th>
				</tr>
				<tr>
					<th v-for="place in places">
						{{ place }}
					</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="row in items">

					<td v-if="row['break'] === null" class="time-col">{{ row['time'] | date('HH:mm') }}</td>
					<td v-for="place in row['places']"  v-if="row['break'] === null">
						<template v-if="place">{{ place }} группа</template>
					</td>

					<td  v-if="row['break'] != null">&nbsp;</td>
					<td  v-if="row['break'] != null" :colspan="places.length" >
						<div class="shedule-break">{{ row['break'] }}</div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
	export default {
		name: "sheduleDefault",
		props: {
			places: {
				type: Array,
				default: () => {
					return []
				}
			},
			items: {
				type: Array,
				default: () => {
					return []
				}
			},
		},
	}
</script>