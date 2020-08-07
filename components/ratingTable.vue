<template>
	<div class="responsive-table-container">
		<table>
		<thead>
			<tr>
				<th class="shooter-place-col">
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'place')" :class="{active: sort.field === 'place', desc: sort.order === 'desc'}">№</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'name')" :class="{active: sort.field === 'name', desc: sort.order === 'desc'}">ФИО</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'group')" :class="{active: sort.field === 'group', desc: sort.order === 'desc'}">Категория</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'competitions')" :class="{active: sort.field === 'competitions', desc: sort.order === 'desc'}">Турниры</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'targets')" :class="{active: sort.field === 'targets', desc: sort.order === 'desc'}">Мишени</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'rating')" :class="{active: sort.field === 'rating', desc: sort.order === 'desc'}">Рейтинг</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'city')" :class="{active: sort.field === 'city', desc: sort.order === 'desc'}">Город</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'club')" :class="{active: sort.field === 'club', desc: sort.order === 'desc'}">Клуб</a>
				</th>
				<th>
					<a href="#" class="table-sort-link" @click.prevent="$emit('sort', 'digit')" :class="{active: sort.field === 'digit', desc: sort.order === 'desc'}">Разряд</a>
				</th>
			</tr>
		</thead>
		<tbody class="table-group" v-for="(shooter, index) in shooters" :class="{'active': active === index}">
			<tr class="shooter-row" @click="toggleGroup(index)">
				<td class="shooter-place-col">
					<div class="table-hidden-title">№</div>
					<div class="shooter-place">
						<span class="shooter-place-num">{{ shooter['place'] }}</span>
						<span class="place-diff" :class="{'diff-negative': shooter['diff'] < 0}" v-if="shooter['diff'] !== 0">{{ parseInt( shooter['diff'] ) }}</span>
					</div>
				</td>
				<td>
					<div class="table-hidden-title">ФИО</div>
					{{ shooter['name'] }}
				</td>
				<td>
					<div class="table-hidden-title">Категория</div>
					{{ shooter['group'] }}
				</td>
				<td>
					<div class="table-hidden-title">Турниры</div>
					{{ shooter['competitions'].length }}
				</td>
				<td>
					<div class="table-hidden-title">Мишени</div>
					{{ shooter['targets'] }}
				</td>
				<td>
					<div class="table-hidden-title">Рейтинг</div>
					{{ shooter['rating'].toFixed(2) }}
				</td>
				<td>
					<div class="table-hidden-title">Город</div>
					<div class="shooter-city">{{ shooter['city'] }}</div>
				</td>
				<td>
					<div class="table-hidden-title">Клуб</div>
					{{ shooter['club'] }}
				</td>
				<td>
					<div class="table-hidden-title">Разряд</div>
					{{ shooter['digit'] }}
				</td>
			</tr>
			<tr class="hidden-row" v-for="item in shooter['competitions']">
				<td colspan="4">
					<div class="hidden-row-tournament-info">
						<div class="shooter-tournament-date">{{ item['date'] | date('DD.MM.YY') }}</div>
						<div class="shooter-tournament-title">{{ item['title'] }}</div>
					</div>
				</td>
				<td><span>{{ item['targets'] }}</span></td>
				<td colspan="4"><span>{{ item['or'].toFixed(2) }}</span></td>
			</tr>
		</tbody>
		<tbody v-if="shooters.length === 0">
			<tr>
				<td colspan="9">Нет результатов</td>
			</tr>
		</tbody>
	</table>
	</div>
</template>

<script>
	export default {
		name: "ratingTable",
		props: {
			sort: {
				type: Object,
				default: () => {
					return {
						field: 'place',
						order: 'asc',
					}
				}
			},
			shooters: {
				type: Array,
				default: () => {
					return []
				}
			}
		},
		data () {
			return {
				active: '',
			}
		},
		watch: {
			shooters () {
				this.active = ''
			}
		},
		methods: {
			toggleGroup (groupInd) {
				this.active = this.active === groupInd ? null : groupInd;
			},
		}
	}
</script>