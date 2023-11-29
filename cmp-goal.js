import { ref, reactive, computed, watchEffect, toRef } from "./vue.js";


let GOAL_TYPE_PERCENTAGE = 1;
let GOAL_TYPE_BINARY = 2;
let GOAL_TYPE_COMPOSITE = 3;

export default {
	props: ["app", "id", "progress"],
	setup(props) {
		let app = props.app;
		let goal = reactive(app.latestGoals[props.id]);
		let id = props.id;
		let progress = toRef(props, "progress");
		return { goal, app, id, progress };
	},
	watch: {
		goal: {
			deep: true,
			handler: function (val, oldVal) {
				// console.log(`goal changed 1 ${JSON.stringify(val)}`);
				// console.log(`goal changed 2 ${JSON.stringify(app.latestGoals[this.id])}`);
				this.app.userChangedGoal(val);
			}
		},
		progress: {
			deep: true,
			handler: function (val, oldVal) {
				//console.log(`goal progress changed`);
			}
		}
	},
	computed: {
		progressDisplay: function () {
			return (this.progress * 100).toFixed(2);
		},
		percentageDisplay: function () {
			return (this.goal.percentage * 100).toFixed(2);
		},
		weightedChildren: function() {
			let weightedChildren = [];
			let weight = 1;
			for (let child of this.goal.children) {
				if (typeof child == "string") {
					let childGoal = app.latestGoals[child];
					weightedChildren.push({weight, id:child, goal:childGoal});
					weight = 1;
				} else if (typeof child == "number") {
					weight = child;
				} else {
					console.error(`unexpected child type ${typeof child}`);
				}
			}
			return weightedChildren;
		}
	},
	methods: {
		newWeight: function(event) {
			//console.log(`new weight ${event.target.value} ${event.target.dataset.childId}`);
			let childGoalID = event.target.dataset.childId;
			let newWeight = parseInt(event.target.value);
			let index = this.goal.children.indexOf(childGoalID);
			if (index < 0) {
				console.error(`child goal ${childGoalID} not found in ${JSON.stringify(this.goal.children)}`);
				return;
			}
			if (index > 0 && typeof this.goal.children[index-1] == "number") {
				this.goal.children[index-1] = newWeight;
			} else {
				this.goal.children.splice(index, 0, newWeight);
			}
			this.app.userChangedGoal(this.goal);
		}
	},
	template: `
		<div class="cmp-goal">
			<div>
				<label>
					<input type="text" class="cmp-goal--name-input" v-model="goal.name">
					{{progressDisplay}}%
				</label>
			</div>
			<div>
				<div v-if="goal.type == ${GOAL_TYPE_PERCENTAGE}">
					<label>
						<input type="range" min="0" max="1" step="0.01" v-model="goal.percentage">
						{{percentageDisplay}}%
					</label>
				</div>
				<div v-if="goal.type == ${GOAL_TYPE_BINARY}">
					<input type="checkbox" v-model="goal.completed">
				</div>
				<div v-if="goal.type == ${GOAL_TYPE_COMPOSITE}">
					<div v-for="child in weightedChildren">
						<div>
							{{child.goal.name}} <input type="number" :data-child-id=child.id :value=child.weight @change="newWeight">
						</div>
					</div>
				</div>
			</div>
		</div>`
};
