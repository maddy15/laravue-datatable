<template>
<div>
    <div class="card">
        <div class="card-header">
            {{ response.table }} Table
            <a href="#" v-if="response.allow.create" class="float-right" @click.prevent="creating.active = !creating.active">
                {{ creating.active ? 'Cancel' : 'Create Record' }}
            </a>
        </div>

        <div class="card-body">
            <div class="card card-body bg-light" v-if="creating.active">
                <form action="#" class="form-horizontal offset-md-3" @submit.prevent="store">
                    <div class="form-group" v-for="(column,key) in response.updatable" :key="key">
                        <label class="col-md-3" :for="column">{{ response.custom_columns[column] || column }}</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" :class="{'has-error' : creating.errors[column]}" :id="column" v-model="creating.form[column]">
                        </div>
                        <span class="help-block" v-if="creating.errors[column]">
                                <strong>{{ creating.errors[column][0] }}</strong>
                        </span>
                    </div>
                    <div class="form-group">
                        <div class="col-md-6 offset-md-2">
                            <button class="btn btn-primary" type="submit">Add</button>
                        </div>
                    </div>
                </form>
            </div>
            <form action="#" @submit.prevent="getRecords()">
                <label for="search">Search</label>
                <div class="row row-fluid">
                    <div class="form-group col-md-3">
                        <select class="form-control" v-model="search.column">
                            <option :value="column" v-for="(column,key) in response.displayable" :key="key">{{ column }}</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <select class="form-control" v-model="search.operator">
                            <option value="equals">=</option>
                            <option value="contains">contains</option>
                            <option value="starts_with">starts with</option>
                            <option value="ends_with">ends with</option>
                            <option value="greater_than">greater than</option>
                            <option value="less_than">less than</option>
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <div class="input-group">
                            <input type="text" id="search" class="form-control" v-model="search.value">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="submit">Search</button>
                            </span>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">
                <div class="form-group col-md-10">
                    <label for="filter">Quick Search Current Results</label>
                    <input type="text" id="filter" class="form-control" v-model="quickSearchQuery"> 
                </div>
                <div class="form-group col-md-2">
                    <label for="limit">Display Record</label>
                    <select id="limit" class="form-control" v-model="limit" @change="getRecords"> 
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="1000">1000</option>
                        <option value="">All</option>
                    </select>
                </div>
            </div>
            
        </div>
    </div>
    <br><br>
    <div class="card">
        <div class="card-header" v-if="selected.length">
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                   With Selected:
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="#" @click.prevent="destroy(selected)">Delete</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                    <table class="table table-stripped">
                        <thead>
                            <tr>
                                 <th v-if="canSelectItems">
                                    <input type="checkbox" :checked="filteredRecords.length === selected.length" @change="toggleSelectAll">
                                </th>
                                <th v-for="(column,key) in response.displayable" :key="key">
                                    <span class="sortable" @click="sortBy(column)">{{ response.custom_columns[column] || column }}</span> 
                                    <div 
                                    class="arrow" 
                                    :class="{ 
                                        'arrow--desc' : sort.order === 'desc', 
                                        'arrow--asc' : sort.order === 'asc'}"
                                        v-if="sort.key == column"
                                        ></div>
                                </th>
                                <th>
                                    &nbsp;
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(record,key) in filteredRecords" :key="key">
                                 <th v-if="canSelectItems">
                                    <input type="checkbox" v-model="selected" :value="record.id">
                                </th>
                                <td v-for="(columnValue,column) in record" :key="column">
                                    <template v-if="editing.id === record.id && isUpdatable(column)">
                                        <div class="form-group" :class="{'has-error' : editing.errors[column]}">
                                            <input type="text" class="form-control" v-model="editing.form[column]">
                                        </div>
                                        <span class="help-block" v-if="editing.errors[column]">
                                                <strong>{{ editing.errors[column][0] }}</strong>
                                        </span>
                                    </template>
                                    <template v-else>
                                        {{ columnValue }}
                                    </template>
                                </td>
                                <td>
                                    <a href="#" @click.prevent="edit(record)" v-if="editing.id !== record.id">Edit</a>

                                    <template v-if="editing.id === record.id">
                                        <a href="#" @click.prevent="save()">Save</a> <br>
                                        <a href="#" @click.prevent="editing.id = null">Cancel</a>
                                    </template>
                                </td>
                                <td>
                                    <a href="#" @click.prevent="destroy(record.id)" v-if="response.allow.delete">Delete</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
        </div>
    </div>
</div>
</template>

<script>
    import queryString from 'query-string';

    export default {
        props:['endpoint'],
        data() {
            return {
                response: {
                    table : '',
                    displayable : [],
                    updatable : [],
                    records : [],
                    allow: {}
                },
                sort:{
                    key: 'id',
                    order: 'asc'
                },
                quickSearchQuery: '',
                limit: 50,
                editing:{
                    id : null,
                    form : {},
                    errors : []
                },
                search:{
                    value: '',
                    operator:'equals',
                    column:'id'
                },
                creating:{
                    active : false,
                    form : {},
                    errors : []
                },
                selected: []
            }
        },
        methods: {
            getRecords() 
            {
                return axios.get(`${this.endpoint}?${this.getQueryParameters()}`)
                .then(response => {
                    this.response = response.data.data;
                });
            },
            getQueryParameters()
            {
                return queryString.stringify({
                    limit : this.limit,
                    ...this.search
                })
            },
            sortBy(column)
            {
               this.sort.key = column;
               this.sort.order = this.sort.order === 'asc' ? 'desc' : 'asc';
            },
            edit(record)
            {
                this.editing.id = record.id;
                this.editing.errors = [];
                this.editing.form = _.pick(record,this.response.updatable);
            },
            isUpdatable(column)
            {
                return this.response.updatable.includes(column);
            },
            save()
            {
                
                axios.patch(`${this.endpoint}/${this.editing.id}`,this.editing.form)
                    .then((res) => {
                        console.log(res);
                        this.getRecords().then(() => {
                            this.editing.id = null;
                            this.editing.errors = [];
                            this.editing.form = {};
                        })
                        
                    })
                    .catch( e => {
                        if(e.response.status === 422)
                        {
                            this.editing.errors = e.response.data.errors;
                        }
                    });
                
            },
            store()
            {
               axios.post(this.endpoint,this.creating.form)
                .then( res => {
                    console.log(res);
                    this.creating.active = false;
                    this.creating.form = {};
                    this.creating.errors = [];
                    this.getRecords();
                })
                .catch(e => {
                    if(e.response.status === 422)
                    {
                        this.creating.errors = e.response.data.errors;
                    }
                });
            },
            destroy(id)
            {
                if(!window.confirm('Are you sure you want to delete this?')) return;
                axios.delete(`${this.endpoint}/${id}`)
                    .then( res => {
                        this.selected = [];
                        this.getRecords();
                    });
            },
            toggleSelectAll()
            {
                if(this.selected.length > 0)
                {
                    this.selected = [];
                    return;
                }
                this.selected = _.map(this.filteredRecords,'id');
            }
        },
        watch: {
            'sort.key'(newValue) {
                this.sort.order = 'asc';
            }
        },
        computed: {
            filteredRecords() {
                let data = this.response.records;
                
                data = data.filter( row => {

                    return Object.keys(row).some( key => {
                        if(key === 'created_at' || key === 'id') return false;
                        return String(row[key]).toLowerCase().indexOf(this.quickSearchQuery.toLowerCase()) > -1;
                    });
                });

                if(this.sort.key)
                {
                    data = _.orderBy(data, (i) => {
                        let value = i[this.sort.key];

                        if(!isNaN(parseFloat(value)))
                        {
                            return parseFloat(value);
                        }

                        return String(i[this.sort.key]).toLowerCase()
                    },this.sort.order);
                }

                return data;
            },
            canSelectItems()
            {
                return this.filteredRecords.length <= 500;
            }
        },
         mounted() {
            this.getRecords();
        },
    }
</script>











<style lang="scss">
.sortable
{
    cursor: pointer
}

.has-error
{
    border: 1px solid red;
}
.help-block
{
    color:red;
}
.arrow
{
    display: inline-block;
    vertical-align: middle;
    width: 0;
    height: 0;
    margin-left : 5px;
    opacity: .6;

    &--asc {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-bottom: 4px solid #222;
    }

     &--desc {
        border-left: 4px solid transparent;
        border-right: 4px solid transparent;
        border-top: 4px solid #222;
    }
    
}

</style>
