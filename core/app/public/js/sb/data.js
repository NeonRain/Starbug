define(['dojo', 'sb/kernel', 'starbug', 'starbug/store/Api'], function(dojo, sb, starbug) {
			sb.get = function(model, action) {
				if (typeof this.stores[model+'.'+action] != 'undefined') return this.stores[model+'.'+action];
				var store = new starbug.store.Api({model:model, action:action});
				this.stores[model+'.'+action] = store;
				return store;
			};
			sb.query = function(model, action, query) {
				if (!query) query = {};
				if (typeof query == 'string') query = this.star(query);
				return this.get(model, action).query(query);
			},
			sb.store = function(model, fields) {
				return this.get(model).put(fields).then(dojo.hitch(this, function(data) {
					if (data.errors) {
						if (typeof this.errors[model] == 'undefined') this.errors[model] = {};
						for (var field in data.errors) {
							field = data.errors[field];
							this.errors[model][field.field] = field.errors;
						}
					}
				}));
			}
			return sb;
});
